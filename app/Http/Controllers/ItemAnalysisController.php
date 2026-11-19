<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Question;
use App\Models\Result;
use App\Models\Test;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemAnalysisController extends Controller
{
    public function facultyIndex(Request $request): View
    {
        $faculty = $request->user();
        $validated = $request->validate([
            'course_id' => ['nullable', 'integer'],
            'test_id' => ['nullable', 'integer'],
            'topic' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['nullable', Rule::in(['all', 'easy', 'medium', 'moderate', 'hard'])],
            'review' => ['nullable', Rule::in(['all', 'needs-review'])],
            'sort' => ['nullable', Rule::in(['default', 'most-missed', 'most-correct', 'most-incorrect', 'fewest-responses'])],
        ]);
        $courses = Course::query()
            ->where('department_id', $faculty->department_id)
            ->orderBy('name')
            ->get(['id', 'name']);
        $selectedCourseId = isset($validated['course_id']) ? (int) $validated['course_id'] : null;
        abort_if(
            $selectedCourseId !== null && ! $courses->contains('id', $selectedCourseId),
            404,
        );
        $tests = Test::query()
            ->where('department_id', $faculty->department_id)
            ->where('is_scheduled_exam', true)
            ->where('is_published', true)
            ->whereIn('course_id', $courses->pluck('id'))
            ->with('course:id,name')
            ->withCount('results')
            ->latest('available_from')
            ->get();
        $selectedTest = null;
        $analysis = [];
        $summary = null;
        $topics = [];
        $filters = [
            'topic' => $validated['topic'] ?? '',
            'difficulty' => $validated['difficulty'] ?? 'all',
            'review' => $validated['review'] ?? 'all',
            'sort' => $validated['sort'] ?? 'default',
        ];
        if ($filters['difficulty'] === 'medium') {
            $filters['difficulty'] = 'moderate';
        }
        if ($filters['sort'] === 'most-missed') {
            $filters['sort'] = 'most-incorrect';
        }

        if ($request->filled('test_id')) {
            $selectedTest = $tests->firstWhere('id', $validated['test_id']);
            abort_if($selectedTest === null, 404);
            abort_if(
                $selectedCourseId !== null && (int) $selectedTest->course_id !== $selectedCourseId,
                404,
            );
            $selectedCourseId ??= (int) $selectedTest->course_id;
            $selectedTest->load('questions.topicRecord', 'questions.questionBankItem.topicRecord');
            $allAnalysis = $this->buildAnalysis($selectedTest);
            $summary = $this->buildFacultySummary($selectedTest, $allAnalysis);
            $topics = collect($allAnalysis)
                ->pluck('topic')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
            if ($filters['topic'] !== '' && ! in_array($filters['topic'], $topics, true)) {
                $filters['topic'] = '';
            }
            $analysis = $this->filterAnalysis($allAnalysis, $filters);
        }

        return view('admin.item-analysis.index', compact(
            'courses',
            'selectedCourseId',
            'tests',
            'selectedTest',
            'analysis',
            'summary',
            'topics',
            'filters',
        ));
    }

    public function exportFaculty(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'test_id' => ['required', 'integer'],
        ]);
        $test = Test::query()
            ->where('department_id', $request->user()->department_id)
            ->where('is_scheduled_exam', true)
            ->where('is_published', true)
            ->findOrFail($validated['test_id']);
        $test->load('questions.topicRecord', 'questions.questionBankItem.topicRecord');
        $analysis = $this->buildAnalysis($test);

        return response()->streamDownload(function () use ($analysis, $test): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new RuntimeException('Unable to open the item analysis export stream.');
            }

            try {
                $writeRow = function (array $values) use ($output): void {
                    $values = array_map(fn (mixed $value): string => $this->safeCsvCell($value), $values);
                    if (fputcsv($output, $values, ',', '"', '') === false) {
                        throw new RuntimeException('Unable to write the item analysis export.');
                    }
                };

                $writeRow([
                    'Examination',
                    'Question',
                    'Topic',
                    'Responses',
                    'Correct',
                    'Correct %',
                    'Incorrect',
                    'Incorrect %',
                    'Most selected incorrect answer',
                    'Answer key',
                    'Review status',
                ]);

                foreach ($analysis as $row) {
                    $writeRow([
                        $test->title,
                        $row['question'],
                        $row['topic'] ?? '',
                        $row['total'],
                        $row['correct'],
                        $row['correct_percentage'].'%',
                        $row['incorrect'],
                        $row['incorrect_percentage'].'%',
                        $row['most_selected_distractor']['label'] ?? '',
                        $row['correct_answer'] ?? '',
                        $row['is_difficult'] ? 'Needs review' : 'No high-miss-rate flag',
                    ]);
                }
            } finally {
                fclose($output);
            }
        }, 'item-analysis-'.$test->id.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
            $selectedTest->load('questions.topicRecord', 'questions.questionBankItem.topicRecord');
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
     * @return array<int, array{question: string, question_id: ?int, question_bank_item_id: ?int, topic_id: ?int, topic: ?string, difficulty: ?string, total: int, correct: int, incorrect: int, correct_percentage: int, incorrect_percentage: int, is_difficult: bool, options: array<int, string>, correct_answer: ?string, explanation: ?string, most_selected_distractor: ?array{label: string, count: int, percentage: int, is_correct: bool}, student_responses: array<int, array{student_name: string, answer: string, is_correct: bool}>, response_breakdown: array<int, array{label: string, count: int, percentage: int, is_correct: bool}>}>
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
            $topicRecord = $this->questionTopicRecord($question);
            $rows[$key] = [
                'question' => $question->question,
                'question_id' => $question->id,
                'question_bank_item_id' => $question->question_bank_item_id,
                'topic_id' => $topicRecord?->id,
                'topic' => $topicRecord?->name
                    ?? $question->questionBankItem?->topic
                    ?? $question->topic
                    ?? $test->topic,
                'difficulty' => $question->difficulty ?? $question->questionBankItem?->difficulty,
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
                'student_responses' => [],
            ];
            $questionKeysByText[$this->normalizedQuestionText($question->question)] ??= $key;
        }

        $latestResultIds = Result::query()
            ->where('test_id', $test->id)
            ->selectRaw('MAX(id)')
            ->groupBy('user_id');

        foreach (Result::query()
            ->whereIn('id', $latestResultIds)
            ->with('user:id,name')
            ->select(['id', 'user_id', 'answers'])
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
                    'question_id' => $questionId,
                    'question_bank_item_id' => $questionBankItemId,
                    'topic_id' => isset($answer['topic_id']) ? (int) $answer['topic_id'] : null,
                    'topic' => isset($answer['topic']) ? (string) $answer['topic'] : $test->topic,
                    'difficulty' => is_string($answer['difficulty'] ?? null) ? $answer['difficulty'] : null,
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
                    'student_responses' => [],
                ];
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

                $this->recordStudentResponse(
                    $rows[$key],
                    $result,
                    $responseLabel,
                    filter_var($answer['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                );
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
            $incorrectResponses = array_values(array_filter(
                $row['response_breakdown'],
                fn (array $response): bool => ! $response['is_correct']
                    && $response['count'] > 0
                    && $response['label'] !== 'Unanswered',
            ));
            usort($incorrectResponses, fn (array $left, array $right): int => $right['count'] <=> $left['count']);
            $row['most_selected_distractor'] = $correctAnswerLabel !== null
                ? ($incorrectResponses[0] ?? null)
                : null;
            usort(
                $row['student_responses'],
                fn (array $left, array $right): int => strcasecmp($left['student_name'], $right['student_name']),
            );
            unset($row['response_counts']);
        }
        unset($row);

        return array_values($rows);
    }

    private function questionTopicRecord(Question $question): ?Topic
    {
        $bankTopic = $question->questionBankItem?->topicRecord;
        if ($bankTopic?->is_folder) {
            return $bankTopic;
        }

        $questionTopic = $question->topicRecord;

        return $questionTopic?->is_folder ? $questionTopic : null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function recordStudentResponse(array &$row, Result $result, string $responseLabel, bool $isCorrect): void
    {
        $row['total']++;
        $responseKey = mb_strtolower($responseLabel);
        $row['response_counts'][$responseKey] ??= ['label' => $responseLabel, 'count' => 0];
        $row['response_counts'][$responseKey]['count']++;
        $row['student_responses'][] = [
            'student_name' => $result->user?->name ?? 'Student unavailable',
            'answer' => $responseLabel,
            'is_correct' => $isCorrect,
        ];

        if ($isCorrect) {
            $row['correct']++;
        } else {
            $row['incorrect']++;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $analysis
     * @return array<string, int|null>
     */
    private function buildFacultySummary(Test $test, array $analysis): array
    {
        $latestResultIds = Result::query()
            ->where('test_id', $test->id)
            ->selectRaw('MAX(id)')
            ->groupBy('user_id');
        $resultSummary = Result::query()
            ->whereIn('id', $latestResultIds)
            ->whereHas('user', fn ($query) => $query
                ->where('role', 'student')
                ->where('status', 'approved')
                ->where('department_id', $test->department_id)
                ->when($test->course_id !== null, fn ($query) => $query->where('course_id', $test->course_id)))
            ->selectRaw('COUNT(*) as participant_count')
            ->selectRaw('AVG(CASE WHEN total_questions > 0 THEN score * 100.0 / total_questions END) as average_score_percentage')
            ->first();
        $eligibleStudents = User::query()
            ->where('role', 'student')
            ->where('status', 'approved')
            ->where('department_id', $test->department_id)
            ->when($test->course_id !== null, fn ($query) => $query->where('course_id', $test->course_id))
            ->count();
        $participants = (int) ($resultSummary->participant_count ?? 0);

        return [
            'participants' => $participants,
            'eligible_students' => $eligibleStudents,
            'completion_percentage' => $eligibleStudents > 0
                ? (int) round(($participants / $eligibleStudents) * 100)
                : null,
            'average_score_percentage' => $resultSummary->average_score_percentage === null
                ? null
                : (int) round((float) $resultSummary->average_score_percentage),
            'questions_to_review' => count(array_filter(
                $analysis,
                fn (array $row): bool => $row['is_difficult'],
            )),
            'question_count' => count($analysis),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $analysis
     * @param  array{topic: string, difficulty: string, review: string, sort: string}  $filters
     * @return array<int, array<string, mixed>>
     */
    private function filterAnalysis(array $analysis, array $filters): array
    {
        $filtered = array_values(array_filter($analysis, function (array $row) use ($filters): bool {
            if ($filters['topic'] !== '' && $row['topic'] !== $filters['topic']) {
                return false;
            }

            $difficulty = strtolower((string) ($row['difficulty'] ?? ''));
            if ($difficulty === 'medium') {
                $difficulty = 'moderate';
            }
            if ($filters['difficulty'] !== 'all' && $difficulty !== $filters['difficulty']) {
                return false;
            }

            if ($filters['review'] === 'needs-review' && ! $row['is_difficult']) {
                return false;
            }

            return true;
        }));

        if ($filters['sort'] === 'most-incorrect') {
            usort($filtered, fn (array $left, array $right): int => ($right['incorrect_percentage'] <=> $left['incorrect_percentage'])
                ?: ($right['total'] <=> $left['total']));
        } elseif ($filters['sort'] === 'most-correct') {
            usort($filtered, fn (array $left, array $right): int => ($right['correct_percentage'] <=> $left['correct_percentage'])
                ?: ($right['total'] <=> $left['total']));
        } elseif ($filters['sort'] === 'fewest-responses') {
            usort($filtered, fn (array $left, array $right): int => $left['total'] <=> $right['total']);
        }

        return $filtered;
    }

    private function safeCsvCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');

        return preg_match('/^[\x00-\x20]*[=+\-@]/', $cell) === 1
            ? "'".$cell
            : $cell;
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
