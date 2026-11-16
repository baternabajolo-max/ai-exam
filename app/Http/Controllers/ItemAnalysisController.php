<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Result;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemAnalysisController extends Controller
{
    public function facultyIndex(Request $request): View
    {
        $faculty = $request->user();
        $tests = Test::query()
            ->where('department_id', $faculty->department_id)
            ->where('is_scheduled_exam', true)
            ->where('is_published', true)
            ->withCount('results')
            ->latest('available_from')
            ->get();
        $selectedTest = null;
        $analysis = [];

        if ($request->filled('test_id')) {
            $validated = $request->validate(['test_id' => ['required', 'integer']]);
            $selectedTest = $tests->firstWhere('id', $validated['test_id']);
            abort_if($selectedTest === null, 404);
            $selectedTest->load('questions');
            $analysis = $this->buildAnalysis($selectedTest);
        }

        return view('admin.item-analysis.index', compact('tests', 'selectedTest', 'analysis'));
    }

    public function adminIndex(Request $request): View
    {
        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'test_id' => ['nullable', 'integer', 'exists:tests,id'],
        ]);
        abort_if(isset($validated['test_id']) && ! isset($validated['course_id']), 404);
        $courses = Course::query()->with('department:id,name')->orderBy('name')->get();
        $testsQuery = Test::query()
            ->where('is_scheduled_exam', true)
            ->where('approval_status', Test::APPROVAL_STATUS_APPROVED)
            ->where('is_published', true)
            ->with(['course:id,name,department_id', 'course.department:id,name'])
            ->withCount('results')
            ->when(
                isset($validated['course_id']),
                fn ($query) => $query->where('course_id', $validated['course_id']),
            )
            ->latest('available_from');
        $tests = $testsQuery->get();
        $selectedTest = null;
        $analysis = [];

        if (isset($validated['test_id'])) {
            $selectedTest = $tests
                ->where('course_id', (int) $validated['course_id'])
                ->firstWhere('id', (int) $validated['test_id']);
            abort_if($selectedTest === null, 404);
            $selectedTest->load('questions');
            $analysis = $this->buildAnalysis($selectedTest);
        }

        return view('super-admin.item-analysis.index', [
            'courses' => $courses,
            'tests' => $tests,
            'selectedCourseId' => $validated['course_id'] ?? null,
            'selectedTest' => $selectedTest,
            'analysis' => $analysis,
        ]);
    }

    /**
     * @return array<int, array{question: string, topic: ?string, total: int, correct: int, incorrect: int, correct_percentage: int, incorrect_percentage: int, is_difficult: bool, options: array<int, string>, correct_answer: ?string, explanation: ?string, response_breakdown: array<int, array{label: string, count: int, percentage: int, is_correct: bool}>}>
     */
    private function buildAnalysis(Test $test): array
    {
        $rows = [];
        $questionKeysByText = [];
        foreach ($test->questions as $question) {
            $key = $this->questionKey(
                $question->question,
                $question->id,
                $question->question_bank_item_id,
            );
            $rows[$key] = [
                'question' => $question->question,
                'topic' => $question->topic ?? $test->topic,
                'options' => is_array($question->options) ? array_values($question->options) : [],
                'correct_answer' => $question->correct_answer,
                'explanation' => $question->explanation,
                'total' => 0,
                'correct' => 0,
                'incorrect' => 0,
                'correct_percentage' => 0,
                'incorrect_percentage' => 0,
                'is_difficult' => false,
                'response_counts' => [],
            ];
            $questionKeysByText[$this->normalizedQuestionText($question->question)] ??= $key;
        }

        $latestResultIds = Result::query()
            ->where('test_id', $test->id)
            ->selectRaw('MAX(id)')
            ->groupBy('user_id');

        foreach (Result::query()
            ->whereIn('id', $latestResultIds)
            ->select(['id', 'answers'])
            ->lazyById(100) as $result) {
            foreach ($result->answers ?? [] as $answer) {
                if (! is_array($answer)) {
                    continue;
                }

                $questionText = trim((string) ($answer['question_text'] ?? $answer['question'] ?? ''));
                if ($questionText === '') {
                    continue;
                }

                $questionId = isset($answer['question_id']) ? (int) $answer['question_id'] : null;
                $questionBankItemId = isset($answer['question_bank_item_id']) ? (int) $answer['question_bank_item_id'] : null;
                $key = $questionId === null && $questionBankItemId === null
                    ? ($questionKeysByText[$this->normalizedQuestionText($questionText)]
                        ?? $this->questionKey($questionText, null, null))
                    : $this->questionKey($questionText, $questionId, $questionBankItemId);
                $rows[$key] ??= [
                    'question' => $questionText,
                    'topic' => isset($answer['topic']) ? (string) $answer['topic'] : $test->topic,
                    'options' => is_array($answer['options'] ?? null) ? array_values($answer['options']) : [],
                    'correct_answer' => is_scalar($answer['correct_answer'] ?? null)
                        ? (string) $answer['correct_answer']
                        : null,
                    'explanation' => is_scalar($answer['explanation'] ?? null)
                        ? (string) $answer['explanation']
                        : null,
                    'total' => 0,
                    'correct' => 0,
                    'incorrect' => 0,
                    'correct_percentage' => 0,
                    'incorrect_percentage' => 0,
                    'is_difficult' => false,
                    'response_counts' => [],
                ];
                $rows[$key]['total']++;

                $userAnswer = $answer['user_answer'] ?? $answer['student_answer'] ?? null;
                $responseLabel = is_scalar($userAnswer) ? trim((string) $userAnswer) : '';
                if ($responseLabel === '') {
                    $responseLabel = 'Unanswered';
                } else {
                    foreach ($rows[$key]['options'] as $optionIndex => $option) {
                        $optionLabel = trim((string) $option);
                        if (
                            strcasecmp($responseLabel, $optionLabel) === 0
                            || (strlen($responseLabel) === 1 && strtoupper($responseLabel) === chr(65 + $optionIndex))
                        ) {
                            $responseLabel = $optionLabel;
                            break;
                        }
                    }
                }
                $responseKey = mb_strtolower($responseLabel);
                $rows[$key]['response_counts'][$responseKey] ??= ['label' => $responseLabel, 'count' => 0];
                $rows[$key]['response_counts'][$responseKey]['count']++;

                if (filter_var($answer['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $rows[$key]['correct']++;
                } else {
                    $rows[$key]['incorrect']++;
                }
            }
        }

        foreach ($rows as &$row) {
            $row['correct_percentage'] = $row['total'] > 0
                ? (int) round(($row['correct'] / $row['total']) * 100)
                : 0;
            $row['incorrect_percentage'] = $row['total'] > 0
                ? (int) round(($row['incorrect'] / $row['total']) * 100)
                : 0;
            $row['is_difficult'] = $row['total'] > 0 && $row['incorrect_percentage'] >= 50;

            $observedResponseCounts = $row['response_counts'];
            $row['response_counts'] = [];
            foreach ($row['options'] as $option) {
                $optionLabel = trim((string) $option);
                $optionKey = mb_strtolower($optionLabel);
                $row['response_counts'][$optionKey] = $observedResponseCounts[$optionKey]
                    ?? ['label' => $optionLabel, 'count' => 0];
            }
            foreach ($observedResponseCounts as $responseKey => $response) {
                $row['response_counts'][$responseKey] ??= $response;
            }

            $correctAnswerLabel = $row['correct_answer'];
            foreach ($row['options'] as $optionIndex => $option) {
                $optionLabel = trim((string) $option);
                if (
                    $correctAnswerLabel !== null
                    && (
                        strcasecmp($correctAnswerLabel, $optionLabel) === 0
                        || (strlen($correctAnswerLabel) === 1 && strtoupper($correctAnswerLabel) === chr(65 + $optionIndex))
                    )
                ) {
                    $correctAnswerLabel = $optionLabel;
                    break;
                }
            }

            $row['response_breakdown'] = array_map(
                fn (array $response): array => [
                    ...$response,
                    'percentage' => $row['total'] > 0
                        ? (int) round(($response['count'] / $row['total']) * 100)
                        : 0,
                    'is_correct' => $correctAnswerLabel !== null
                        && strcasecmp($response['label'], $correctAnswerLabel) === 0,
                ],
                array_values($row['response_counts']),
            );
            unset($row['response_counts']);
        }
        unset($row);

        return array_values($rows);
    }

    private function questionKey(string $question, ?int $questionId, ?int $questionBankItemId): string
    {
        if ($questionId !== null) {
            return 'question:'.$questionId;
        }

        if ($questionBankItemId !== null) {
            return 'bank:'.$questionBankItemId;
        }

        return 'legacy:'.sha1($this->normalizedQuestionText($question));
    }

    private function normalizedQuestionText(string $question): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', mb_strtolower(trim($question))) ?? '';
    }
}
