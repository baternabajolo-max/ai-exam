<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Result;
use App\Models\StudentNotification;
use App\Models\Subtopic;
use App\Models\Test;
use App\Models\TestRetakeGrant;
use App\Models\User;
use App\Services\LeaderboardPointsService;
use App\Services\StudentReadinessService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = auth()->user();
        $departmentId = $user->department_id;
        $programName = $user->department?->name ?? 'College not assigned';

        $validatedPeriod = $request->validate([
            'period' => ['sometimes', 'in:7,30,90,custom'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'before_or_equal:end_date'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:today'],
        ]);
        $period = (string) ($validatedPeriod['period'] ?? '30');
        $periodStartDate = $period === 'custom' ? $validatedPeriod['start_date'] : null;
        $periodEndDate = $period === 'custom' ? $validatedPeriod['end_date'] : null;
        $periodEnd = $period === 'custom'
            ? CarbonImmutable::parse($periodEndDate)->endOfDay()
            : CarbonImmutable::now();
        $periodStart = $period === 'custom'
            ? CarbonImmutable::parse($periodStartDate)->startOfDay()
            : $periodEnd->startOfDay()->subDays((int) $period - 1);
        $periodDays = (int) $periodStart->diffInDays($periodEnd->startOfDay()) + 1;

        if ($periodDays > 366) {
            return back()
                ->withErrors(['start_date' => 'Choose a custom reporting range of 366 days or fewer.'])
                ->withInput();
        }

        $periodQuery = $period === 'custom'
            ? ['period' => $period, 'start_date' => $periodStartDate, 'end_date' => $periodEndDate]
            : ['period' => $period];
        $periodLabel = $period === 'custom'
            ? $periodStart->format('M j, Y').' – '.$periodEnd->format('M j, Y')
            : 'Last '.$periodDays.' days';
        $periodDescription = $period === 'custom'
            ? 'during '.$periodLabel
            : 'in the last '.$periodDays.' days';
        $periodDateRange = $periodStart->format('M j, Y').' – '.$periodEnd->format('M j, Y');
        $departmentStudents = User::query()
            ->inFacultyCollege($user)
            ->where('role', 'student')
            ->count();
        $departmentStudentQuery = User::query()
            ->inFacultyCollege($user)
            ->where('role', 'student')
            ->where(function ($query) use ($departmentId, $periodStart, $periodEnd): void {
                $query->whereHas('results', fn ($resultQuery) => $resultQuery
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->whereHas('test', fn ($testQuery) => $testQuery
                        ->where('department_id', $departmentId)))
                    ->orWhereHas('studySessions', fn ($sessionQuery) => $sessionQuery
                        ->whereBetween('started_at', [$periodStart, $periodEnd])
                        ->where('active_seconds', '>', 0));
            });
        $activeStudentsCount = (clone $departmentStudentQuery)->count();
        $inactiveStudentsCount = max(0, $departmentStudents - $activeStudentsCount);
        $studentSearch = trim($request->string('student_search')->toString());
        $matchingStudents = User::query()
            ->inFacultyCollege($user)
            ->where('role', 'student')
            ->when($studentSearch !== '', function ($query) use ($studentSearch) {
                $query->where(function ($query) use ($studentSearch) {
                    $query->where('name', 'like', "{$studentSearch}%")
                        ->orWhere('email', 'like', "{$studentSearch}%")
                        ->orWhere('student_id_number', 'like', "{$studentSearch}%");
                });
            })
            ->limit(10)
            ->get();
        $periodResults = Result::query()
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereHas('test', fn ($query) => $query
                ->where('department_id', $departmentId)
                ->where('is_general', true))
            ->whereHas('user', fn ($query) => $query
                ->inFacultyCollege($user)
                ->where('role', 'student')
            )
            ->with(['test:id,title', 'user:id,name,email'])
            ->latest()
            ->get(['id', 'user_id', 'test_id', 'score', 'total_questions', 'created_at']);
        $summarizeStudentResults = fn ($results) => $results
            ->groupBy('user_id')
            ->map(fn ($studentResults) => $studentResults->first());
        $latestPeriodResults = $summarizeStudentResults($periodResults);
        $scorePercentages = $latestPeriodResults->map(fn (Result $result): int => $this->resultPercentage($result));
        $generalTestParticipants = $scorePercentages->count();
        $averageCollegeScore = $scorePercentages->isNotEmpty() ? (int) round($scorePercentages->avg()) : null;
        $collegePassRate = $scorePercentages->isNotEmpty()
            ? (int) round(($scorePercentages->filter(fn (int $score): bool => $score >= 75)->count() / $scorePercentages->count()) * 100)
            : null;
        $departmentPerformance = collect(range(0, $periodDays - 1))
            ->map(function (int $day) use ($periodStart, $periodResults, $summarizeStudentResults): array {
                $date = $periodStart->addDays($day);
                $dailyResults = $periodResults->filter(
                    fn (Result $result): bool => $result->created_at->toDateString() === $date->toDateString()
                );
                $latestStudentResults = $summarizeStudentResults($dailyResults);
                $score = $latestStudentResults->isNotEmpty()
                    ? (int) round($latestStudentResults->avg(fn (Result $result): int => $this->resultPercentage($result)))
                    : null;

                return [
                    'date' => $date->format('Y-m-d'),
                    'label' => $date->format($date->isMonday() || $date->isLastOfMonth() ? 'M j' : 'j'),
                    'score' => $score,
                    'participants' => $latestStudentResults->count(),
                ];
            });
        $supportCandidates = [];
        foreach ($latestPeriodResults as $result) {
            $score = $this->resultPercentage($result);
            if ($score < 75 && $result->user !== null) {
                $supportCandidates[$result->user_id] = [
                    'student' => $result->user,
                    'score' => $score,
                    'reason' => 'Latest general-test score is below the 75% target.',
                    'priority' => $score,
                ];
            }
        }
        $studentsWithoutRecentActivity = User::query()
            ->inFacultyCollege($user)
            ->where('role', 'student')
            ->whereDoesntHave('results', fn ($query) => $query
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->whereHas('test', fn ($testQuery) => $testQuery
                    ->where('department_id', $departmentId)))
            ->whereDoesntHave('studySessions', fn ($query) => $query
                ->whereBetween('started_at', [$periodStart, $periodEnd])
                ->where('active_seconds', '>', 0))
            ->oldest()
            ->limit(8)
            ->get(['id', 'name', 'email']);
        foreach ($studentsWithoutRecentActivity as $studentWithoutRecentActivity) {
            $supportCandidates[$studentWithoutRecentActivity->id] = [
                'student' => $studentWithoutRecentActivity,
                'score' => null,
                'reason' => 'No study or assessment activity in the selected period.',
                'priority' => -1,
            ];
        }
        $supportStudents = collect($supportCandidates)
            ->sortBy('priority')
            ->take(8)
            ->values();

        return view('admin.dashboard', compact(
            'programName',
            'departmentStudents',
            'activeStudentsCount',
            'inactiveStudentsCount',
            'studentSearch',
            'matchingStudents',
            'departmentPerformance',
            'period',
            'periodDays',
            'periodStartDate',
            'periodEndDate',
            'periodLabel',
            'periodDescription',
            'periodDateRange',
            'periodQuery',
            'averageCollegeScore',
            'generalTestParticipants',
            'collegePassRate',
            'supportStudents',
        ));
    }

    public function students()
    {
        $admin = auth()->user();
        $pendingStudents = User::query()
            ->inFacultyCollege($admin)
            ->where('role', 'student')
            ->where('status', 'pending')
            ->with(['department', 'course'])
            ->get();

        $approvedStudents = User::query()
            ->inFacultyCollege($admin)
            ->where('role', 'student')
            ->where('status', 'approved')
            ->with(['department', 'course'])
            ->get();

        return view('admin.students.index', compact('pendingStudents', 'approvedStudents'));
    }

    public function studentSearchSuggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'college_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);
        $search = trim($validated['q'] ?? '');

        if ($search === '') {
            return response()->json(['data' => []]);
        }

        $studentQuery = User::query()
            ->select(['id', 'name', 'email', 'student_id_number'])
            ->where('role', 'student')
            ->where(function ($query) use ($search): void {
                $query->where('name', 'like', "{$search}%")
                    ->orWhere('email', 'like', "{$search}%")
                    ->orWhere('student_id_number', 'like', "{$search}%");
            });

        if ($request->user()->role === 'admin') {
            $studentQuery->inFacultyCollege($request->user());
        } elseif (isset($validated['college_id'])) {
            $studentQuery->where('department_id', $validated['college_id']);
        }

        $profileRoute = $request->user()->role === 'super-admin'
            ? 'super-admin.students.show'
            : 'admin.students.show';
        $students = $studentQuery->orderBy('name')
            ->limit(8)
            ->get()
            ->map(function (User $student) use ($profileRoute, $validated, $search): array {
                $url = $profileRoute === 'super-admin.students.show'
                    ? route($profileRoute, [
                        'student' => $student,
                        'college_id' => $validated['college_id'] ?? null,
                        'student_search' => $search,
                    ])
                    : route($profileRoute, $student);

                return [
                    'name' => $student->name,
                    'email' => $student->email,
                    'url' => $url,
                ];
            });

        return response()->json(['data' => $students]);
    }

    public function leaderboard(LeaderboardPointsService $leaderboardPointsService)
    {
        $admin = auth()->user();
        $students = $admin->department_id === null
            ? collect()
            : User::query()
                ->inFacultyCollege($admin)
                ->where('role', 'student')
                ->get();

        foreach ($students as $student) {
            $leaderboardPointsService->recalculateFor($student);
            $student->achievements()
                ->where('required_points', '>', $student->total_points)
                ->detach();
        }

        $leaders = $admin->department_id === null
            ? collect()
            : User::query()
                ->inFacultyCollege($admin)
                ->where('role', 'student')
                ->with('department')
                ->orderByDesc('total_points')
                ->orderBy('name')
                ->orderBy('id')
                ->take(10)
                ->get();

        return view('admin.leaderboard.index', compact('leaders'));
    }

    public function notifications()
    {
        $notifications = StudentNotification::where('admin_id', auth()->id())
            ->with('student:id,name,email')
            ->latest()
            ->paginate(15);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function activateExamRetake(Request $request, User $student, Test $test): RedirectResponse
    {
        $actor = $request->user();
        abort_unless(
            $actor->role === 'super-admin'
                || ($actor->role === 'admin' && (int) $actor->department_id === (int) $test->department_id),
            404,
        );

        $alreadyActive = DB::transaction(function () use ($actor, $student, $test): bool {
            $lockedTest = Test::query()->lockForUpdate()->findOrFail($test->id);
            abort_unless(
                $student->role === 'student'
                    && $student->course_id !== null
                    && (int) $student->department_id === (int) $lockedTest->department_id
                    && (int) $student->course_id === (int) $lockedTest->course_id
                    && $lockedTest->is_scheduled_exam
                    && $lockedTest->isAvailableAt()
                    && $lockedTest->results()->where('user_id', $student->id)->exists(),
                404,
            );

            $grant = TestRetakeGrant::query()
                ->where('test_id', $lockedTest->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($grant !== null && $grant->consumed_at === null) {
                return true;
            }

            if ($grant === null) {
                TestRetakeGrant::create([
                    'test_id' => $lockedTest->id,
                    'student_id' => $student->id,
                    'granted_by' => $actor->id,
                    'granted_at' => now(),
                ]);
            } else {
                $grant->update([
                    'granted_by' => $actor->id,
                    'granted_at' => now(),
                    'started_at' => null,
                    'consumed_at' => null,
                ]);
            }

            return false;
        });

        return back()->with(
            'success',
            $alreadyActive
                ? 'A retake is already active for this examination.'
                : 'One retake has been activated for '.$student->name.'.',
        );
    }

    public function profile()
    {
        return view('admin.profile.index', [
            'profileUser' => auth()->user(),
            'profileRoute' => 'admin.profile.update',
            'passwordRoute' => 'admin.profile.password.update',
        ]);
    }

    public function approveStudent(User $student)
    {
        $admin = auth()->user();
        if ($student->department_id === $admin->department_id
            && $student->role === 'student') {
            $student->update(['status' => 'approved']);

            return back()->with('success', 'Student approved successfully.');
        }

        return back()->withErrors(['error' => 'Unauthorized action on student record.']);
    }

    public function rejectStudent(User $student)
    {
        $admin = auth()->user();
        if ($student->department_id === $admin->department_id
            && $student->role === 'student') {
            $student->delete();

            return back()->with('success', 'Student rejected and removed successfully.');
        }

        return back()->withErrors(['error' => 'Unauthorized action on student record.']);
    }

    public function showStudent(Request $request, User $student, StudentReadinessService $readinessService): View
    {
        $isFacultyViewer = $request->user()->role === 'admin';
        if ($isFacultyViewer) {
            $this->ensureStudentBelongsToAuthenticatedAdmin($student);
        } else {
            abort_unless(
                $request->user()->role === 'super-admin' && $student->role === 'student',
                404,
            );
        }

        $profileBackUrl = $isFacultyViewer
            ? route('admin.dashboard')
            : route('super-admin.dashboard', $request->only(['college_id', 'student_search']));
        $performanceRoute = $isFacultyViewer
            ? route('admin.students.show', $student)
            : route('super-admin.students.show', array_merge(
                ['student' => $student],
                $request->only(['college_id', 'student_search']),
            ));

        $validatedPeriod = $request->validate([
            'period' => ['sometimes', 'in:7,custom'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'before_or_equal:end_date'],
            'end_date' => [
                'required_if:period,custom',
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                'before_or_equal:today',
                function (string $attribute, mixed $value, Closure $fail) use ($request): void {
                    $startDate = $request->input('start_date');

                    if (! is_string($startDate) || ! is_string($value)
                        || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)
                        || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        return;
                    }

                    [$startYear, $startMonth, $startDay] = array_map('intval', explode('-', $startDate));
                    [$endYear, $endMonth, $endDay] = array_map('intval', explode('-', $value));

                    if (! checkdate($startMonth, $startDay, $startYear)
                        || ! checkdate($endMonth, $endDay, $endYear)) {
                        return;
                    }

                    if (CarbonImmutable::parse($startDate)->diffInDays(CarbonImmutable::parse($value)) > 6) {
                        $fail('Choose a date range of 7 days or fewer.');
                    }
                },
            ],
        ]);
        $periodStartDate = $validatedPeriod['start_date'] ?? CarbonImmutable::now()->subDays(6)->toDateString();
        $periodEndDate = $validatedPeriod['end_date'] ?? CarbonImmutable::now()->toDateString();
        $periodStart = CarbonImmutable::parse($periodStartDate)->startOfDay();
        $periodEnd = CarbonImmutable::parse($periodEndDate)->endOfDay();
        $analyticsDays = $periodStart->diffInDays($periodEnd->startOfDay()) + 1;

        $student->load([
            'department',
            'course',
            'achievements' => fn ($query) => $query->wherePivotNotNull('claimed_at'),
        ]);
        $modules = Module::forStudent($student)
            ->with('subtopics')
            ->orderBy('title')
            ->get();
        $moduleProgress = $modules->mapWithKeys(function (Module $module) use ($student): array {
            $subtopics = $module->subtopics->mapWithKeys(function (Subtopic $subtopic) use ($student): array {
                return [$subtopic->id => $this->studentSubtopicPoints($student, $subtopic)];
            });

            return [$module->id => [
                'points' => $subtopics->sum(),
                'maximum' => $module->subtopics->count() * 30,
                'subtopics' => $subtopics,
            ]];
        });

        $generalTestHistory = $this->generalTestHistory($student);
        $examHistory = Result::query()
            ->where('user_id', $student->id)
            ->whereHas('test', fn ($query) => $query
                ->where('is_scheduled_exam', true)
                ->where('department_id', $student->department_id)
                ->where('course_id', $student->course_id))
            ->with([
                'test.course:id,name',
                'test.retakeGrants' => fn ($query) => $query
                    ->where('student_id', $student->id)
                    ->whereNull('consumed_at'),
            ])
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->groupBy('test_id');

        $periodResults = Result::query()
            ->where('user_id', $student->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereHas('test', fn ($query) => $query
                ->where('course_id', $student->course_id)
                ->where('is_general', true)
                ->where('title', 'not like', '% - Review Quiz'))
            ->latest()
            ->get(['id', 'test_id', 'score', 'total_questions', 'created_at']);
        $studentAnalytics = [
            'attempts' => $periodResults->count(),
            'passing_attempts' => $periodResults
                ->filter(fn (Result $result): bool => $this->resultPercentage($result) >= 75)
                ->count(),
        ];
        $dailyScores = $periodResults
            ->groupBy(fn (Result $result): string => $result->created_at->format('Y-m-d'))
            ->map(fn ($results): array => [
                'score' => $this->resultPercentage($results->first()),
                'attempts' => $results->count(),
            ]);
        $studentPerformance = collect(range(0, $analyticsDays - 1))
            ->map(function (int $day) use ($periodStart, $dailyScores): array {
                $date = $periodStart->addDays($day);

                return [
                    'date' => $date->format('Y-m-d'),
                    'score' => $dailyScores[$date->format('Y-m-d')]['score'] ?? null,
                    'attempts' => $dailyScores[$date->format('Y-m-d')]['attempts'] ?? 0,
                ];
            });
        [
            $readinessModules,
            $readinessScore,
            $readinessAttemptsCount,
            $readinessAssessedModuleCount,
            $readinessAssessedQuestionCount,
        ] = $readinessService->forStudent($student, $modules);
        $weakestModule = collect($readinessModules)
            ->filter(fn (array $module): bool => $module['score'] !== null)
            ->sortBy('score')
            ->first();
        $weakestModuleRecord = $weakestModule === null
            ? null
            : $modules->first(fn (Module $module): bool => $module->title === $weakestModule['module']);

        return view('admin.students.show', compact(
            'student',
            'modules',
            'moduleProgress',
            'generalTestHistory',
            'examHistory',
            'analyticsDays',
            'periodStartDate',
            'periodEndDate',
            'periodStart',
            'periodEnd',
            'studentAnalytics',
            'studentPerformance',
            'readinessModules',
            'readinessScore',
            'readinessAttemptsCount',
            'readinessAssessedModuleCount',
            'readinessAssessedQuestionCount',
            'weakestModule',
            'weakestModuleRecord',
            'isFacultyViewer',
            'profileBackUrl',
            'performanceRoute',
        ));
    }

    public function showStudentGeneralTestHistory(Request $request, User $student, StudentReadinessService $readinessService): View
    {
        return $this->showStudent($request, $student, $readinessService);
    }

    public function sendNotification(Request $request, User $student): RedirectResponse
    {
        $this->ensureStudentBelongsToAuthenticatedAdmin($student);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        StudentNotification::create([
            'subject' => auth()->user()->name,
            'message' => $validated['message'],
            'student_id' => $student->id,
            'admin_id' => auth()->id(),
        ]);

        return back()->with('success', 'Notification sent to '.$student->name.'.');
    }

    private function ensureStudentBelongsToAuthenticatedAdmin(User $student): void
    {
        abort_unless(
            $student->role === 'student'
                && $student->department_id === auth()->user()->department_id,
            404,
        );
    }

    /**
     * @return Collection<int, Result>
     */
    private function generalTestHistory(User $student): Collection
    {
        return Result::query()
            ->where('user_id', $student->id)
            ->whereHas('test', fn ($query) => $query->where('is_general', true))
            ->with('test:id,title')
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->each(function (Result $result): void {
                $result->display_total_questions = $this->resultTotalQuestions($result);
                $result->display_percentage = $this->resultPercentage($result);
                $result->display_answers = $this->resultAnswers($result);
            });
    }

    /**
     * @return array<int, array{question_text: string, user_answer: ?string, correct_answer: ?string, is_correct: ?bool}>
     */
    private function resultAnswers(Result $result): array
    {
        $answers = $result->answers;
        if (is_string($answers)) {
            $answers = json_decode($answers, true);
        }

        if (! is_array($answers) || $answers === []) {
            $answers = $result->answers_summary;
            if (is_string($answers)) {
                $answers = json_decode($answers, true);
            }
        }

        if (! is_array($answers)) {
            return [];
        }

        return collect($answers)
            ->filter(static fn (mixed $answer): bool => is_array($answer))
            ->map(function (array $answer): array {
                $userAnswer = $this->answerValue($answer['user_answer'] ?? $answer['student_answer'] ?? null);
                $correctAnswer = $this->answerValue($answer['correct_answer'] ?? null);
                $isCorrect = $answer['is_correct'] ?? null;

                if (in_array($isCorrect, [0, 1, '0', '1'], true)) {
                    $isCorrect = (bool) $isCorrect;
                }

                if (! is_bool($isCorrect) && $userAnswer !== null && $correctAnswer !== null) {
                    $isCorrect = mb_strtolower(trim($userAnswer)) === mb_strtolower(trim($correctAnswer));
                }

                return [
                    'question_text' => (string) ($answer['question_text'] ?? $answer['question'] ?? ''),
                    'user_answer' => $userAnswer,
                    'correct_answer' => $correctAnswer,
                    'is_correct' => is_bool($isCorrect) ? $isCorrect : null,
                ];
            })
            ->values()
            ->all();
    }

    private function answerValue(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = implode(', ', array_map(
                static fn (mixed $answer): string => is_scalar($answer) ? (string) $answer : '',
                $value,
            ));
        }

        if (! is_scalar($value)) {
            return null;
        }

        $formattedValue = (string) $value;

        return trim($formattedValue) === '' ? null : $formattedValue;
    }

    private function resultPercentage(Result $result): int
    {
        $totalQuestions = $this->resultTotalQuestions($result);

        return $totalQuestions > 0 ? (int) round(($result->score / $totalQuestions) * 100) : 0;
    }

    private function resultTotalQuestions(Result $result): int
    {
        $answers = $result->answers;
        if (is_string($answers)) {
            $answers = json_decode($answers, true);
        }

        if (! is_array($answers) || $answers === []) {
            $answers = $result->answers_summary;
            if (is_string($answers)) {
                $answers = json_decode($answers, true);
            }
        }

        return (int) ($result->total_questions ?: (is_array($answers) ? count($answers) : 0));
    }

    private function studentSubtopicPoints(User $student, Subtopic $subtopic): int
    {
        $latestResult = Result::where('user_id', $student->id)
            ->whereHas('test', fn ($query) => $query
                ->where('course_id', $student->course_id)
                ->where(function ($query) use ($subtopic): void {
                    $query->where('topic', $subtopic->title)
                        ->orWhere('title', 'like', '%'.$subtopic->title.'%');
                }))
            ->latest('created_at')
            ->latest('id')
            ->first();

        if (! $latestResult) {
            return 0;
        }

        return (int) round($this->resultPercentage($latestResult) * 30 / 100);
    }
}
