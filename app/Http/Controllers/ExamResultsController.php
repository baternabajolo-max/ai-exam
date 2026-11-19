<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\Result;
use App\Models\Test;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ExamResultsController extends Controller
{
    public function facultyIndex(Request $request): View
    {
        return $this->index($request, false);
    }

    public function adminIndex(Request $request): View
    {
        return $this->index($request, true);
    }

    public function facultyStudent(Request $request, Test $exam, User $student): View
    {
        return $this->student($request, $exam, $student, false);
    }

    public function adminStudent(Request $request, Test $exam, User $student): View
    {
        return $this->student($request, $exam, $student, true);
    }

    private function index(Request $request, bool $isAdmin): View
    {
        $validated = $request->validate([
            'college_id' => ['nullable', 'integer', 'exists:departments,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'test_id' => ['nullable', 'integer'],
            'student_search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:all,passed,failed,not_taken'],
        ]);
        abort_if(! $isAdmin && isset($validated['college_id']), 404);

        $departments = $isAdmin
            ? Department::query()->orderBy('name')->get(['id', 'name'])
            : collect();
        $selectedDepartmentId = isset($validated['college_id']) ? (int) $validated['college_id'] : null;
        $selectedCourseId = isset($validated['course_id']) ? (int) $validated['course_id'] : null;
        $selectedTestId = isset($validated['test_id']) ? (int) $validated['test_id'] : null;

        if ($isAdmin && $selectedCourseId !== null && $selectedDepartmentId === null) {
            abort(404);
        }

        if ($isAdmin) {
            $courses = Course::query()
                ->orderBy('name')
                ->get(['id', 'name', 'department_id']);
        } else {
            $faculty = $request->user();
            $courses = $faculty->department_id === null
                ? collect()
                : Course::query()
                    ->where('department_id', $faculty->department_id)
                    ->orderBy('name')
                    ->orderBy('id')
                    ->get(['id', 'name', 'department_id']);
        }

        if ($selectedCourseId !== null) {
            $selectedCourse = $courses->firstWhere('id', $selectedCourseId);
            abort_if($selectedCourse === null, 404);
            abort_if(
                $isAdmin && (int) $selectedCourse->department_id !== $selectedDepartmentId,
                404,
            );
        }

        $tests = $selectedCourseId === null
            ? collect()
            : Test::query()
                ->where('course_id', $selectedCourseId)
                ->where('is_scheduled_exam', true)
                ->where('is_published', true)
                ->where('approval_status', Test::APPROVAL_STATUS_APPROVED)
                ->with('course:id,name')
                ->orderByDesc('available_from')
                ->orderByDesc('id')
                ->get();

        if ($selectedTestId !== null && $selectedCourseId === null) {
            abort(404);
        }

        $selectedTest = $selectedTestId === null ? null : $tests->firstWhere('id', $selectedTestId);
        abort_if($selectedTestId !== null && $selectedTest === null, 404);

        $studentResults = collect();
        $summary = [
            'student_count' => 0,
            'participant_count' => 0,
            'participation_percentage' => 0,
            'average_percentage' => null,
            'passing_count' => 0,
            'timeout_count' => 0,
        ];
        $studentSearch = trim((string) ($validated['student_search'] ?? ''));
        $statusFilter = $validated['status'] ?? 'all';
        $timeLimitSeconds = null;

        if ($selectedTest !== null) {
            $students = User::query()
                ->where('role', 'student')
                ->where('course_id', $selectedCourseId)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'course_id']);

            $latestAttemptIds = Result::query()
                ->where('test_id', $selectedTest->id)
                ->selectRaw('MAX(id)')
                ->groupBy('user_id');
            $attemptsByStudent = Result::query()
                ->where('test_id', $selectedTest->id)
                ->whereIn('user_id', $students->modelKeys())
                ->whereIn('id', $latestAttemptIds)
                ->get()
                ->keyBy('user_id');

            $studentResults = $students->map(function (User $student) use ($attemptsByStudent): array {
                $attempt = $attemptsByStudent->get($student->id);
                $percentage = $attempt === null ? null : $this->resultPercentage($attempt);

                return [
                    'student' => $student,
                    'attempt' => $attempt,
                    'percentage' => $percentage,
                    'total_questions' => $attempt === null ? null : $this->resultTotalQuestions($attempt),
                    'passed' => $percentage !== null && $percentage >= Test::PASSING_SCORE_PERCENTAGE,
                    'rank' => null,
                    'time_taken_label' => $attempt?->time_taken_seconds === null
                        ? '—'
                        : $this->formatDuration((int) $attempt->time_taken_seconds),
                ];
            })->sort(function (array $left, array $right): int {
                if ($left['attempt'] === null || $right['attempt'] === null) {
                    return $left['attempt'] === $right['attempt']
                        ? strcasecmp($left['student']->name, $right['student']->name)
                        : ($left['attempt'] === null ? 1 : -1);
                }

                return ($right['percentage'] <=> $left['percentage'])
                    ?: (($left['attempt']->time_taken_seconds ?? PHP_INT_MAX) <=> ($right['attempt']->time_taken_seconds ?? PHP_INT_MAX))
                    ?: strcasecmp($left['student']->name, $right['student']->name);
            })->values();

            $rank = 0;
            foreach ($studentResults as $index => $studentResult) {
                if ($studentResult['attempt'] !== null) {
                    $studentResult['rank'] = ++$rank;
                    $studentResults[$index] = $studentResult;
                }
            }

            $attemptedResults = $studentResults
                ->pluck('attempt')
                ->filter();
            $studentCount = $students->count();
            $participantCount = $attemptedResults->count();
            $summary = [
                'student_count' => $studentCount,
                'participant_count' => $participantCount,
                'participation_percentage' => $studentCount > 0
                    ? (int) round(($participantCount / $studentCount) * 100)
                    : 0,
                'average_percentage' => $participantCount > 0
                    ? round($attemptedResults->avg(fn (Result $result): float => $this->resultPercentage($result)), 1)
                    : null,
                'passing_count' => $attemptedResults
                    ->filter(fn (Result $result): bool => $this->resultPercentage($result) >= Test::PASSING_SCORE_PERCENTAGE)
                    ->count(),
                'timeout_count' => $attemptedResults->filter(
                    fn (Result $result): bool => $result->submission_type === 'time_expired',
                )->count(),
            ];
            $timeLimitMinutes = $selectedTest->time_limit ?? $selectedTest->duration_minutes;
            $timeLimitSeconds = $timeLimitMinutes === null ? null : (int) $timeLimitMinutes * 60;

            $studentResults = $studentResults
                ->filter(function (array $studentResult) use ($studentSearch, $statusFilter): bool {
                    $status = $studentResult['attempt'] === null
                        ? 'not_taken'
                        : ($studentResult['passed'] ? 'passed' : 'failed');

                    return ($statusFilter === 'all' || $statusFilter === $status)
                        && ($studentSearch === '' || mb_stripos($studentResult['student']->name, $studentSearch) !== false);
                })
                ->values();
        }

        $indexRoute = $isAdmin ? 'super-admin.results.index' : 'faculty.results.index';
        $studentRoute = $isAdmin ? 'super-admin.results.student' : 'faculty.results.student';
        $timeLimitLabel = $timeLimitSeconds === null ? '—' : $this->formatDuration($timeLimitSeconds);

        return view('results.index', compact(
            'isAdmin',
            'departments',
            'courses',
            'tests',
            'selectedDepartmentId',
            'selectedCourseId',
            'selectedTest',
            'studentResults',
            'summary',
            'studentSearch',
            'statusFilter',
            'timeLimitSeconds',
            'timeLimitLabel',
            'indexRoute',
            'studentRoute',
        ));
    }

    private function student(Request $request, Test $exam, User $student, bool $isAdmin): View
    {
        abort_unless(
            $exam->is_scheduled_exam
                && $exam->is_published
                && $exam->approval_status === Test::APPROVAL_STATUS_APPROVED
                && $exam->course_id !== null,
            404,
        );
        abort_unless($student->role === 'student' && (int) $student->course_id === (int) $exam->course_id, 404);

        if ($isAdmin) {
            $validated = $request->validate([
                'college_id' => ['nullable', 'integer', 'exists:departments,id'],
                'course_id' => ['nullable', 'integer', 'exists:courses,id'],
                'student_search' => ['nullable', 'string', 'max:100'],
                'status' => ['nullable', 'in:all,passed,failed,not_taken'],
            ]);
            abort_if(
                (isset($validated['college_id']) && (int) $validated['college_id'] !== (int) $exam->department_id)
                    || (isset($validated['course_id']) && (int) $validated['course_id'] !== (int) $exam->course_id),
                404,
            );
        } else {
            $faculty = $request->user();
            abort_unless(
                $faculty->department_id !== null
                    && (int) $exam->department_id === (int) $faculty->department_id
                    && Course::query()
                        ->whereKey($exam->course_id)
                        ->where('department_id', $faculty->department_id)
                        ->exists(),
                404,
            );
        }

        $result = Result::query()
            ->where('test_id', $exam->id)
            ->where('user_id', $student->id)
            ->latest('created_at')
            ->latest('id')
            ->firstOrFail();
        $exam->load('questions:id,test_id,topic,difficulty,correct_answer');
        $questionsById = $exam->questions->keyBy('id');
        $answers = is_array($result->answers) ? $result->answers : [];
        $items = collect($answers)
            ->filter(fn (mixed $answer): bool => is_array($answer))
            ->map(function (array $answer, int $index) use ($questionsById, $exam): array {
                $question = isset($answer['question_id']) ? $questionsById->get((int) $answer['question_id']) : null;
                $selectedAnswer = $answer['user_answer'] ?? null;
                $selectedAnswer = is_scalar($selectedAnswer) ? trim((string) $selectedAnswer) : '';
                $questionText = $answer['question_text'] ?? $answer['question'] ?? '';
                $questionText = is_scalar($questionText) ? (string) $questionText : '';
                $rawCorrectAnswer = $answer['correct_answer'] ?? null;
                if (! is_scalar($rawCorrectAnswer) || trim((string) $rawCorrectAnswer) === '') {
                    $rawCorrectAnswer = $question?->correct_answer;
                }
                $rawDifficulty = $answer['difficulty'] ?? $question?->difficulty ?? '';
                $difficulty = is_scalar($rawDifficulty) ? mb_strtolower(trim((string) $rawDifficulty)) : '';
                $difficulty = $difficulty === 'medium' ? 'moderate' : $difficulty;
                $rawTopic = $answer['topic'] ?? $question?->topic ?? $exam->topic ?? '';
                $topic = is_scalar($rawTopic) ? trim((string) $rawTopic) : '';

                return [
                    'item_number' => $index + 1,
                    'question' => $questionText,
                    'topic' => $topic !== '' ? $topic : 'Uncategorized',
                    'difficulty' => $difficulty,
                    'selected_answer' => $selectedAnswer,
                    'correct_answer' => is_scalar($rawCorrectAnswer)
                        ? (string) $rawCorrectAnswer
                        : '',
                    'explanation' => is_scalar($answer['explanation'] ?? null)
                        ? (string) $answer['explanation']
                        : '',
                    'is_correct' => filter_var($answer['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'is_unanswered' => $selectedAnswer === '',
                ];
            })
            ->values();

        $totalItems = $items->count();
        $totalQuestions = $this->resultTotalQuestions($result);
        $correctCount = $items->where('is_correct', true)->count();
        $unansweredCount = $items->where('is_unanswered', true)->count();
        $scorePercentage = $this->resultPercentage($result);
        $passed = $scorePercentage >= Test::PASSING_SCORE_PERCENTAGE;
        $timeLimitMinutes = $exam->time_limit ?? $exam->duration_minutes;
        $timeLimitSeconds = $timeLimitMinutes === null ? null : (int) $timeLimitMinutes * 60;
        $timeUsedSeconds = $result->time_taken_seconds;
        $timeUsedLabel = $timeUsedSeconds === null ? '—' : $this->formatDuration((int) $timeUsedSeconds);
        $timeLimitLabel = $timeLimitSeconds === null ? '—' : $this->formatDuration($timeLimitSeconds);
        $averagePaceSeconds = $timeUsedSeconds !== null && $totalItems > 0
            ? (int) round($timeUsedSeconds / $totalItems)
            : null;
        $targetPaceSeconds = $timeLimitSeconds !== null && $totalItems > 0
            ? (int) round($timeLimitSeconds / $totalItems)
            : null;

        $difficultyStats = collect([
            'easy' => 'Easy',
            'moderate' => 'Moderate',
            'hard' => 'Hard',
        ])->map(function (string $label, string $difficulty) use ($items): array {
            $difficultyItems = $items->where('difficulty', $difficulty);
            $total = $difficultyItems->count();
            $correct = $difficultyItems->where('is_correct', true)->count();

            return [
                'key' => $difficulty,
                'label' => $label,
                'total' => $total,
                'correct' => $correct,
                'accuracy' => $total > 0 ? round(($correct / $total) * 100, 1) : null,
            ];
        })->values();

        $topicStats = $items
            ->groupBy('topic')
            ->map(function (Collection $topicItems, string $topic): array {
                $total = $topicItems->count();
                $correct = $topicItems->where('is_correct', true)->count();
                $accuracy = $total > 0 ? round(($correct / $total) * 100, 1) : 0;

                return [
                    'topic' => $topic,
                    'total' => $total,
                    'correct' => $correct,
                    'accuracy' => $accuracy,
                    'status' => $accuracy >= 75 ? 'Strong' : ($accuracy >= 60 ? 'Needs Review' : 'Weak Area'),
                ];
            })
            ->sortBy('accuracy')
            ->values();

        $weakestTopic = $topicStats->first();
        $weakestDifficulty = $difficultyStats
            ->filter(fn (array $difficulty): bool => $difficulty['total'] > 0)
            ->sortBy('accuracy')
            ->first();

        $validatedFilters = $request->validate([
            'item_filter' => ['nullable', 'in:all,incorrect,unanswered'],
            'topic' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['nullable', 'in:easy,moderate,medium,hard'],
        ]);
        $itemFilter = $validatedFilters['item_filter'] ?? 'all';
        $selectedTopic = $validatedFilters['topic'] ?? '';
        $selectedDifficulty = $validatedFilters['difficulty'] ?? '';
        if ($selectedDifficulty === 'medium') {
            $selectedDifficulty = 'moderate';
        }

        $filteredItems = $items
            ->filter(function (array $item) use ($itemFilter, $selectedTopic, $selectedDifficulty): bool {
                return ($itemFilter === 'all'
                        || ($itemFilter === 'incorrect' && ! $item['is_correct'] && ! $item['is_unanswered'])
                        || ($itemFilter === 'unanswered' && $item['is_unanswered']))
                    && ($selectedTopic === '' || $item['topic'] === $selectedTopic)
                    && ($selectedDifficulty === '' || $item['difficulty'] === $selectedDifficulty);
            })
            ->values();

        $indexRoute = $isAdmin ? 'super-admin.results.index' : 'faculty.results.index';
        $studentRoute = $isAdmin ? 'super-admin.results.student' : 'faculty.results.student';
        $backFilters = [
            'college_id' => $isAdmin ? $exam->department_id : null,
            'course_id' => $exam->course_id,
            'test_id' => $exam->id,
            'student_search' => $request->query('student_search'),
            'status' => $request->query('status'),
        ];
        $backUrl = route($indexRoute, array_filter($backFilters, fn (mixed $value): bool => $value !== null && $value !== ''));
        $detailRouteParameters = [
            'exam' => $exam->id,
            'student' => $student->id,
            ...array_filter([
                'college_id' => $isAdmin ? $exam->department_id : null,
                'course_id' => $exam->course_id,
                'student_search' => $request->query('student_search'),
                'status' => $request->query('status'),
            ], fn (mixed $value): bool => $value !== null && $value !== ''),
        ];

        return view('results.student', compact(
            'isAdmin',
            'exam',
            'student',
            'result',
            'items',
            'filteredItems',
            'totalItems',
            'totalQuestions',
            'correctCount',
            'unansweredCount',
            'scorePercentage',
            'passed',
            'timeLimitSeconds',
            'timeLimitLabel',
            'timeUsedSeconds',
            'timeUsedLabel',
            'averagePaceSeconds',
            'targetPaceSeconds',
            'difficultyStats',
            'topicStats',
            'weakestTopic',
            'weakestDifficulty',
            'itemFilter',
            'selectedTopic',
            'selectedDifficulty',
            'backUrl',
            'detailRouteParameters',
            'studentRoute',
        ));
    }

    private function resultPercentage(Result $result): float
    {
        $totalQuestions = $this->resultTotalQuestions($result);

        return $totalQuestions > 0
            ? round(((int) $result->score / $totalQuestions) * 100, 1)
            : 0;
    }

    private function resultTotalQuestions(Result $result): int
    {
        return (int) $result->total_questions > 0
            ? (int) $result->total_questions
            : (is_array($result->answers) ? count($result->answers) : 0);
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0
            ? sprintf('%dh %02dm', $hours, $minutes)
            : $minutes.'m';
    }
}
