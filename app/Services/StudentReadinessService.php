<?php

namespace App\Services;

use App\Models\Result;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class StudentReadinessService
{
    /**
     * @return array{0: array<int, array{module: string, score: int|null, question_count: int}>, 1: int|null, 2: int, 3: int, 4: int}
     */
    public function forStudent(User $student, EloquentCollection $modules): array
    {
        $results = Result::query()
            ->where('user_id', $student->id)
            ->whereHas('test', fn ($query) => $query->where('is_general', true))
            ->with('test.questions:id,test_id,question,topic')
            ->latest()
            ->take(1)
            ->get(['id', 'test_id', 'score', 'total_questions', 'answers', 'answers_summary']);
        $moduleTopicIds = [];
        $moduleStats = [];

        foreach ($modules as $module) {
            $moduleStats[$module->id] = [
                'module' => $module->title,
                'question_count' => 0,
                'correct_count' => 0,
            ];

            foreach ([$module->title, ...$module->subtopics->pluck('title')->all()] as $topicLabel) {
                $normalizedTopic = $this->normalizeQuestion($topicLabel);
                if ($normalizedTopic === '') {
                    continue;
                }

                if (array_key_exists($normalizedTopic, $moduleTopicIds)
                    && $moduleTopicIds[$normalizedTopic] !== $module->id) {
                    $moduleTopicIds[$normalizedTopic] = null;

                    continue;
                }

                $moduleTopicIds[$normalizedTopic] = $module->id;
            }
        }

        $unmatchedAnswerModuleId = count($moduleStats) === 1 ? array_key_first($moduleStats) : null;
        $latestAnswerCount = 0;
        foreach ($results as $result) {
            $test = $result->test;
            if (! $test) {
                continue;
            }

            $answers = $result->answers;
            if (! is_array($answers) || $answers === []) {
                $answers = $result->answers_summary;
            }

            while (is_string($answers)) {
                $answers = json_decode($answers, true);
            }

            if (! is_array($answers)) {
                continue;
            }

            $latestAnswerCount = count($answers);
            $questionTopics = [];
            foreach ($test->questions as $question) {
                $questionTopics['id:'.$question->id] = $question->topic;
                $questionTopics['text:'.$this->normalizeQuestion($question->question)] = $question->topic;
            }

            foreach ($answers as $answerKey => $answer) {
                if (! is_array($answer)) {
                    continue;
                }

                $questionText = (string) ($answer['question_text'] ?? $answer['question'] ?? '');
                $answerTopic = $answer['topic']
                    ?? $questionTopics['id:'.$answerKey]
                    ?? $questionTopics['text:'.$this->normalizeQuestion($questionText)]
                    ?? '';
                $topic = trim((string) $answerTopic);
                $normalizedTopic = mb_strtolower($topic) === 'general'
                    ? ''
                    : $this->normalizeQuestion($topic);
                $moduleId = $normalizedTopic === '' ? null : ($moduleTopicIds[$normalizedTopic] ?? null);
                $moduleId ??= $unmatchedAnswerModuleId;
                if ($moduleId === null) {
                    continue;
                }

                $moduleStats[$moduleId]['question_count']++;

                $isCorrect = $answer['is_correct'] ?? null;
                if (! is_bool($isCorrect)) {
                    $studentAnswer = $answer['user_answer'] ?? $answer['student_answer'] ?? null;
                    $correctAnswer = $answer['correct_answer'] ?? null;
                    $isCorrect = $studentAnswer !== null
                        && $correctAnswer !== null
                        && strcasecmp((string) $studentAnswer, (string) $correctAnswer) === 0;
                }

                if ($isCorrect) {
                    $moduleStats[$moduleId]['correct_count']++;
                }
            }
        }

        $readinessModules = array_map(static function (array $stats): array {
            $isAssessed = $stats['question_count'] > 0;

            return [
                'module' => $stats['module'],
                'score' => $isAssessed
                    ? (int) round(($stats['correct_count'] / $stats['question_count']) * 100)
                    : null,
                'question_count' => $stats['question_count'],
            ];
        }, array_values($moduleStats));

        $latestResult = $results->first();
        $assessedQuestionCount = $latestResult === null
            ? 0
            : (int) ($latestResult->total_questions ?: ($latestAnswerCount ?: array_sum(array_column($moduleStats, 'question_count'))));
        $readinessScore = $latestResult === null || $assessedQuestionCount === 0
            ? null
            : (int) round(($latestResult->score / $assessedQuestionCount) * 100);

        $assessedModuleCount = count(array_filter(
            $readinessModules,
            static fn (array $module): bool => $module['score'] !== null,
        ));

        return [$readinessModules, $readinessScore, $results->count(), $assessedModuleCount, $assessedQuestionCount];
    }

    private function normalizeQuestion(string $question): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', mb_strtolower(trim($question))) ?? '';
    }
}
