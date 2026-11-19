<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Result;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class QuestionBankQualityAnalyzer
{
    /**
     * @param  Collection<int, QuestionBankItem>  $items
     * @return array<int, array{
     *     total_responses: int,
     *     correct_responses: int,
     *     pass_rate: ?float,
     *     usage_count: int,
     *     last_used_at: ?Carbon,
     *     choice_breakdown: array<int, array{
     *         letter: string,
     *         option: string,
     *         count: int,
     *         percentage: float,
     *         is_correct: bool,
     *         is_unused_distractor: bool
     *     }>,
     *     quality_status: string,
     *     unflagged_quality_status: string,
     *     is_easy_miscalibrated: bool
     * }>
     */
    public function analyze(Collection $items): array
    {
        if ($items->isEmpty()) {
            return [];
        }

        $itemsById = $items->keyBy(static fn (QuestionBankItem $item): int => (int) $item->getKey());
        $itemIds = $itemsById->keys()->map(static fn (int|string $id): int => (int) $id)->all();
        $itemIdsLookup = array_fill_keys($itemIds, true);
        $questionTexts = $items->pluck('question')->unique()->values()->all();
        $itemsByExactQuestion = QuestionBankItem::query()
            ->whereIn('question', $questionTexts)
            ->get(['id', 'question'])
            ->groupBy(static fn (QuestionBankItem $item): string => $item->question)
            ->filter(static fn (Collection $matches): bool => $matches->count() === 1)
            ->map(static fn (Collection $matches): int => (int) $matches->first()->getKey())
            ->all();

        $stats = [];
        foreach ($items as $item) {
            $itemId = (int) $item->getKey();
            $options = is_array($item->options) ? array_values($item->options) : [];
            $stats[$itemId] = [
                'total_responses' => 0,
                'correct_responses' => 0,
                'last_used_at' => null,
                'choice_counts' => array_fill(0, count($options), 0),
            ];
        }

        $questions = Question::query()
            ->where(function (Builder $query) use ($itemIds, $itemsByExactQuestion): void {
                $query->whereIn('question_bank_item_id', $itemIds)
                    ->orWhere(function (Builder $legacyQuery) use ($itemsByExactQuestion): void {
                        $legacyQuery->whereNull('question_bank_item_id')
                            ->whereIn('question', array_keys($itemsByExactQuestion));
                    });
            })
            ->get(['id', 'test_id', 'question_bank_item_id', 'question', 'options', 'correct_answer']);

        $itemIdByQuestionId = [];
        $questionDataById = [];
        $usageTestIdsByItem = [];
        foreach ($questions as $question) {
            $itemId = $question->question_bank_item_id !== null
                ? (int) $question->question_bank_item_id
                : ($itemsByExactQuestion[$question->question] ?? null);

            if ($itemId === null || ! isset($itemIdsLookup[$itemId])) {
                continue;
            }

            $itemIdByQuestionId[(int) $question->getKey()] = $itemId;
            $questionDataById[(int) $question->getKey()] = [
                'options' => is_array($question->options) ? $question->options : [],
                'correct_answer' => $question->correct_answer,
            ];
            $usageTestIdsByItem[$itemId][(int) $question->test_id] = true;
        }

        $testIds = $questions->pluck('test_id')->unique()->values()->all();
        if ($testIds !== []) {
            $results = Result::query()
                ->whereIn('test_id', $testIds)
                ->get(['test_id', 'answers', 'answers_summary', 'created_at']);

            foreach ($results as $result) {
                $answers = $this->decodeAnswers($result->answers);
                if ($answers === []) {
                    $answers = $this->decodeAnswers($result->answers_summary);
                }

                $answersAreList = array_is_list($answers);
                foreach ($answers as $answerKey => $answer) {
                    if (is_scalar($answer)) {
                        $answer = ['user_answer' => $answer];
                    } elseif (! is_array($answer)) {
                        continue;
                    }

                    $questionId = isset($answer['question_id']) && is_numeric($answer['question_id'])
                        ? (int) $answer['question_id']
                        : null;
                    if ($questionId === null && ! $answersAreList && is_numeric($answerKey)) {
                        $questionId = (int) $answerKey;
                    }

                    $itemId = $questionId === null
                        ? null
                        : ($itemIdByQuestionId[$questionId] ?? null);
                    $answerBankItemId = isset($answer['question_bank_item_id']) && is_numeric($answer['question_bank_item_id'])
                        ? (int) $answer['question_bank_item_id']
                        : null;

                    if ($itemId === null && $answerBankItemId !== null && isset($itemIdsLookup[$answerBankItemId])) {
                        $itemId = $answerBankItemId;
                    }
                    if ($itemId === null && $questionId === null) {
                        $questionText = $answer['question_text'] ?? $answer['question'] ?? null;
                        if (is_string($questionText)) {
                            $itemId = $itemsByExactQuestion[$questionText] ?? null;
                        }
                    }
                    if ($itemId === null || ! isset($stats[$itemId])) {
                        continue;
                    }
                    $item = $itemsById->get($itemId);
                    if (! $item instanceof QuestionBankItem) {
                        continue;
                    }

                    $stats[$itemId]['total_responses']++;
                    $questionData = $questionId === null ? null : ($questionDataById[$questionId] ?? null);
                    if ($this->responseIsCorrect($answer, $item, $questionData)) {
                        $stats[$itemId]['correct_responses']++;
                    }

                    $userAnswer = $answer['user_answer'] ?? $answer['student_answer'] ?? null;
                    $choiceIndex = $this->choiceIndex($userAnswer, $item->options ?? []);
                    if ($choiceIndex !== null && array_key_exists($choiceIndex, $stats[$itemId]['choice_counts'])) {
                        $stats[$itemId]['choice_counts'][$choiceIndex]++;
                    }

                    $usageTestIdsByItem[$itemId][(int) $result->test_id] = true;
                    if (
                        $result->created_at !== null
                        && (
                            $stats[$itemId]['last_used_at'] === null
                            || $result->created_at->greaterThan($stats[$itemId]['last_used_at'])
                        )
                    ) {
                        $stats[$itemId]['last_used_at'] = $result->created_at;
                    }
                }
            }
        }

        $metrics = [];
        foreach ($items as $item) {
            $itemId = (int) $item->getKey();
            $itemStats = $stats[$itemId];
            $totalResponses = $itemStats['total_responses'];
            $passRate = $totalResponses > 0
                ? round(($itemStats['correct_responses'] / $totalResponses) * 100, 1)
                : null;
            $options = is_array($item->options) ? array_values($item->options) : [];
            $correctChoiceIndex = $this->choiceIndex($item->correct_answer, $options);
            $hasUnusedDistractor = false;
            $choiceBreakdown = [];

            foreach ($options as $index => $option) {
                $choiceCount = $itemStats['choice_counts'][$index] ?? 0;
                $isCorrectChoice = $correctChoiceIndex === $index;
                $isUnusedDistractor = ! $isCorrectChoice && $totalResponses >= 5 && $choiceCount === 0;
                $hasUnusedDistractor = $hasUnusedDistractor || $isUnusedDistractor;
                $choiceBreakdown[] = [
                    'letter' => chr(65 + $index),
                    'option' => (string) $option,
                    'count' => $choiceCount,
                    'percentage' => $totalResponses > 0
                        ? round(($choiceCount / $totalResponses) * 100, 1)
                        : 0.0,
                    'is_correct' => $isCorrectChoice,
                    'is_unused_distractor' => $isUnusedDistractor,
                ];
            }

            $unflaggedStatus = $totalResponses === 0
                ? 'untested'
                : (($passRate < 40 || $hasUnusedDistractor) ? 'needs_review' : 'healthy');
            $metrics[$itemId] = [
                'total_responses' => $totalResponses,
                'correct_responses' => $itemStats['correct_responses'],
                'pass_rate' => $passRate,
                'usage_count' => count($usageTestIdsByItem[$itemId] ?? []),
                'last_used_at' => $itemStats['last_used_at'],
                'choice_breakdown' => $choiceBreakdown,
                'quality_status' => $item->needs_revision ? 'flagged' : $unflaggedStatus,
                'unflagged_quality_status' => $unflaggedStatus,
                'is_easy_miscalibrated' => strtolower((string) $item->difficulty) === 'easy'
                    && $passRate !== null
                    && $passRate < 50,
            ];
        }

        return $metrics;
    }

    /**
     * @return array<mixed>
     */
    private function decodeAnswers(mixed $answers): array
    {
        if (is_string($answers)) {
            $decoded = json_decode($answers, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($answers) ? $answers : [];
    }

    /**
     * @param  array<string, mixed>  $answer
     * @param  array{options: array<int, mixed>, correct_answer: mixed}|null  $questionData
     */
    private function responseIsCorrect(
        array $answer,
        QuestionBankItem $item,
        ?array $questionData = null,
    ): bool {
        $isCorrect = $answer['is_correct'] ?? null;
        if (is_bool($isCorrect)) {
            return $isCorrect;
        }
        if (in_array($isCorrect, [0, 1, '0', '1', 'true', 'false'], true)) {
            return filter_var($isCorrect, FILTER_VALIDATE_BOOLEAN);
        }

        $userAnswer = $answer['user_answer'] ?? $answer['student_answer'] ?? null;
        $correctAnswer = $answer['correct_answer'] ?? $questionData['correct_answer'] ?? $item->correct_answer;
        if (! is_scalar($userAnswer) || ! is_scalar($correctAnswer)) {
            return false;
        }

        $options = is_array($answer['options'] ?? null)
            ? $answer['options']
            : ($questionData['options'] ?? $item->options);
        $userChoiceIndex = $this->choiceIndex($userAnswer, $options);
        $correctChoiceIndex = $this->choiceIndex($correctAnswer, $options);

        if ($userChoiceIndex !== null && $correctChoiceIndex !== null) {
            return $userChoiceIndex === $correctChoiceIndex;
        }

        return mb_strtolower(trim((string) $userAnswer)) === mb_strtolower(trim((string) $correctAnswer));
    }

    /**
     * @param  array<int, mixed>  $options
     */
    private function choiceIndex(mixed $answer, array $options): ?int
    {
        if (! is_scalar($answer)) {
            return null;
        }

        $normalizedAnswer = mb_strtolower(trim((string) $answer));
        if ($normalizedAnswer === '') {
            return null;
        }

        foreach ($options as $index => $option) {
            if (is_scalar($option) && mb_strtolower(trim((string) $option)) === $normalizedAnswer) {
                return $index;
            }
        }

        if (preg_match('/^(?:choice\\s*)?([a-z])(?:[.)]\\s*.*)?$/i', trim((string) $answer), $matches) === 1) {
            $index = ord(strtoupper($matches[1])) - 65;

            return array_key_exists($index, $options) ? $index : null;
        }

        return null;
    }
}
