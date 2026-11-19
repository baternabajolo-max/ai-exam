<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\QuestionBankItem;
use App\Models\Result;
use App\Models\StudySession;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\FacultyMonitoringMessageNotification;
use App\Services\LeaderboardPointsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function monitoring(Request $request): View
    {
        $validated = $request->validate([
            'college_id' => ['nullable', 'integer', 'exists:departments,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ]);
        $selectedDepartmentId = isset($validated['college_id']) ? (int) $validated['college_id'] : null;
        $selectedCourseId = isset($validated['course_id']) ? (int) $validated['course_id'] : null;
        $selectedCourse = $selectedCourseId === null
            ? null
            : Course::query()->with('department:id,name')->findOrFail($selectedCourseId);
        abort_if(
            $selectedCourse !== null
                && $selectedDepartmentId !== null
                && (int) $selectedCourse->department_id !== $selectedDepartmentId,
            404,
        );
        $selectedDepartmentId ??= $selectedCourse?->department_id;
        $selectedDepartment = $selectedDepartmentId === null
            ? null
            : Department::query()->findOrFail($selectedDepartmentId);
        $departments = Department::query()
            ->withCount('questionBankItems')
            ->orderBy('name')
            ->get(['id', 'name']);
        $overview = [
            'total_faculty' => User::query()->where('role', 'admin')->count(),
            'active_faculty' => User::query()->where('role', 'admin')->whereNotNull('email_verified_at')->count(),
            'total_questions' => QuestionBankItem::query()->count(),
            'total_exam_submissions' => Result::query()
                ->whereHas('test', fn ($query) => $query->where('is_scheduled_exam', true))
                ->count(),
        ];
        $monthlyContributions = $this->monthlyQuestionContributions($selectedDepartmentId, $selectedCourseId);
        $allCollegesMonthlyContributions = $selectedDepartmentId === null
            ? $monthlyContributions
            : $this->monthlyQuestionContributions();

        return view('super-admin.monitoring.index', compact(
            'allCollegesMonthlyContributions',
            'departments',
            'overview',
            'monthlyContributions',
            'selectedDepartment',
            'selectedDepartmentId',
            'selectedCourseId',
            'selectedCourse',
        ));
    }

    public function monitoringPrograms(Department $department): JsonResponse
    {
        $programs = Course::query()
            ->where('department_id', $department->id)
            ->withCount('questionBankItems')
            ->orderBy('name')
            ->get(['id', 'name', 'department_id'])
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'name' => $course->name,
                'question_count' => (int) $course->question_bank_items_count,
            ])
            ->values();

        return response()->json([
            'college' => [
                'id' => $department->id,
                'name' => $department->name,
            ],
            'programs' => $programs,
            'monthly_contributions' => $this->monthlyQuestionContributions((int) $department->id),
        ]);
    }

    public function monitoringProgramDetails(Course $course): JsonResponse
    {
        $course->loadMissing('department:id,name');
        $folderRecords = Topic::query()
            ->where('course_id', $course->id)
            ->where('is_folder', true)
            ->withCount('bankItems as questions_count')
            ->orderBy('name')
            ->get(['id', 'course_id', 'name']);
        $folderContributions = QuestionBankItem::query()
            ->whereIn('topic_id', $folderRecords->modelKeys())
            ->whereNotNull('author_id')
            ->select(['topic_id', 'author_id'])
            ->selectRaw('COUNT(*) as question_count')
            ->groupBy('topic_id', 'author_id')
            ->with('author:id,name')
            ->get()
            ->groupBy('topic_id');
        $folders = $folderRecords
            ->map(function (Topic $topic) use ($folderContributions): array {
                $contributors = $folderContributions->get($topic->id, collect())
                    ->filter(fn (QuestionBankItem $item): bool => $item->author !== null)
                    ->map(fn (QuestionBankItem $item): array => [
                        'id' => $item->author_id,
                        'name' => $item->author->name,
                        'question_count' => (int) $item->question_count,
                    ])
                    ->values();

                return [
                    'id' => $topic->id,
                    'name' => $topic->name,
                    'question_count' => (int) $topic->questions_count,
                    'contributor_count' => $contributors->count(),
                    'contributors' => $contributors,
                ];
            })
            ->values();
        $facultyFolders = [];
        foreach ($folders as $folder) {
            foreach ($folder['contributors'] as $contributor) {
                $facultyFolders[$contributor['id']][] = [
                    'id' => $folder['id'],
                    'name' => $folder['name'],
                    'question_count' => $contributor['question_count'],
                ];
            }
        }
        $faculty = User::query()
            ->where('role', 'admin')
            ->where(function ($query) use ($course): void {
                $query->where('department_id', $course->department_id)
                    ->orWhereHas('handledCourses', fn ($query) => $query
                        ->where('courses.department_id', $course->department_id));
            })
            ->withCount([
                'questionBankItems as selected_program_questions_count' => fn ($query) => $query
                    ->where('course_id', $course->id),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'department_id'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'question_count' => (int) $user->selected_program_questions_count,
                'folders' => $facultyFolders[$user->id] ?? [],
            ])
            ->values();
        $programQuestionCount = QuestionBankItem::query()
            ->where('course_id', $course->id)
            ->count();
        $attributedQuestionCount = $faculty->sum('question_count');

        return response()->json([
            'program' => [
                'id' => $course->id,
                'name' => $course->name,
                'college_id' => $course->department_id,
                'college_name' => $course->department->name,
                'question_count' => $programQuestionCount,
            ],
            'folders' => $folders,
            'faculty' => $faculty,
            'unlisted_creator_question_count' => max(0, $programQuestionCount - $attributedQuestionCount),
            'monthly_contributions' => $this->monthlyQuestionContributions(
                (int) $course->department_id,
                (int) $course->id,
            ),
        ]);
    }

    public function monitoringFacultyContributions(Request $request, User $faculty): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);
        $course = Course::query()
            ->with('department:id,name')
            ->findOrFail((int) $validated['course_id']);
        abort_unless($faculty->role === 'admin' && $this->facultyBelongsToDepartment($faculty, (int) $course->department_id), 404);

        $folders = Topic::query()
            ->where('course_id', $course->id)
            ->where('is_folder', true)
            ->withCount([
                'bankItems as questions_count' => fn ($query) => $query->where('author_id', $faculty->id),
            ])
            ->orderBy('name')
            ->get(['id', 'course_id', 'name'])
            ->map(fn (Topic $topic): array => [
                'id' => $topic->id,
                'name' => $topic->name,
                'question_count' => (int) $topic->questions_count,
                'has_contribution' => $topic->questions_count > 0,
            ])
            ->values();
        $contributedFolderCount = $folders->where('question_count', '>', 0)->count();

        return response()->json([
            'faculty' => [
                'id' => $faculty->id,
                'name' => $faculty->name,
            ],
            'college' => $course->department->name,
            'program' => $course->name,
            'total_questions' => (int) QuestionBankItem::query()
                ->where('course_id', $course->id)
                ->where('author_id', $faculty->id)
                ->count(),
            'folder_count' => $contributedFolderCount,
            'total_folder_count' => $folders->count(),
            'folders' => $folders,
        ]);
    }

    public function sendFacultyMessage(Request $request, User $faculty): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $course = Course::query()->findOrFail((int) $validated['course_id']);
        abort_unless($faculty->role === 'admin' && $this->facultyBelongsToDepartment($faculty, (int) $course->department_id), 404);

        $message = trim($validated['message']);
        if ($message === '') {
            throw ValidationException::withMessages([
                'message' => 'Enter a message.',
            ]);
        }

        $faculty->notify(new FacultyMonitoringMessageNotification(
            $request->user()->name,
            $message,
            (int) $course->id,
        ));

        return response()->json([
            'message' => 'Message sent to '.$faculty->name.'.',
        ]);
    }

    private function facultyBelongsToDepartment(User $faculty, int $departmentId): bool
    {
        return (int) $faculty->department_id === $departmentId
            || $faculty->handledCourses()
                ->where('courses.department_id', $departmentId)
                ->exists();
    }

    /**
     * @return array{labels: list<string>, values: list<int>, current_month_count: int, previous_month_count: int, month_delta: int}
     */
    private function monthlyQuestionContributions(?int $departmentId = null, ?int $courseId = null): array
    {
        $now = CarbonImmutable::now();
        $firstMonth = $now->startOfMonth()->subMonths(11);
        $driver = DB::connection()->getDriverName();
        $monthExpression = match ($driver) {
            'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m')",
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "TO_CHAR(created_at, 'YYYY-MM')",
            default => throw new \LogicException("Monthly monitoring is not configured for the {$driver} driver."),
        };
        $groupedQuestions = QuestionBankItem::query()
            ->whereNotNull('author_id')
            ->whereBetween('created_at', [$firstMonth->startOfDay(), $now->endOfMonth()])
            ->when($courseId !== null, fn ($query) => $query->where('course_id', $courseId))
            ->when(
                $departmentId !== null && $courseId === null,
                fn ($query) => $query->whereHas('course', fn ($query) => $query->where('department_id', $departmentId)),
            )
            ->selectRaw("{$monthExpression} as contribution_month, COUNT(id) as question_count")
            ->groupBy('contribution_month')
            ->orderBy('contribution_month')
            ->pluck('question_count', 'contribution_month');
        $labels = [];
        $values = [];

        for ($monthOffset = 0; $monthOffset < 12; $monthOffset++) {
            $month = $firstMonth->addMonths($monthOffset);
            $labels[] = $month->format('M Y');
            $values[] = (int) $groupedQuestions->get($month->format('Y-m'), 0);
        }

        $currentMonthCount = $values[11];
        $previousMonthCount = $values[10];

        return [
            'labels' => $labels,
            'values' => $values,
            'current_month_count' => $currentMonthCount,
            'previous_month_count' => $previousMonthCount,
            'month_delta' => $currentMonthCount - $previousMonthCount,
        ];
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $validatedPeriod = $request->validate([
            'period' => ['sometimes', 'in:7,30,90,custom'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'before_or_equal:end_date'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:today'],
            'college_id' => ['nullable', 'required_with:course_id', 'integer', 'exists:departments,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'student_search' => ['nullable', 'string', 'max:100'],
        ]);
        $studentSearch = trim($validatedPeriod['student_search'] ?? '');
        $period = (string) ($validatedPeriod['period'] ?? '30');
        $requestedDepartmentId = isset($validatedPeriod['college_id']) ? (int) $validatedPeriod['college_id'] : null;
        $selectedCourseId = isset($validatedPeriod['course_id']) ? (int) $validatedPeriod['course_id'] : null;
        $selectedCourse = $selectedCourseId === null
            ? null
            : Course::query()->with('department:id,name')->findOrFail($selectedCourseId);
        abort_if(
            $selectedCourse !== null
                && $requestedDepartmentId !== null
                && $selectedCourse->department_id !== $requestedDepartmentId,
            404,
        );
        $selectedDepartmentId = $selectedCourse?->department_id ?? $requestedDepartmentId;
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

        $periodLabel = $period === 'custom'
            ? $periodStart->format('M j, Y').' - '.$periodEnd->format('M j, Y')
            : 'Last '.$periodDays.' days';
        $departments = Department::withCount(['users' => function ($query) use ($selectedCourseId): void {
            $query->where('role', 'student')
                ->when($selectedCourseId !== null, fn ($query) => $query->where('course_id', $selectedCourseId));
        }])->orderBy('name')->get();
        $courses = $requestedDepartmentId === null
            ? collect()
            : Course::query()
                ->where('department_id', $requestedDepartmentId)
                ->with('department:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'department_id']);
        $selectedDepartment = $departments->firstWhere('id', $selectedDepartmentId);
        $matchingStudents = collect();
        if ($studentSearch !== '') {
            $matchingStudents = User::query()
                ->select(['id', 'name', 'email', 'student_id_number', 'department_id', 'course_id'])
                ->where('role', 'student')
                ->when($selectedDepartmentId !== null, fn ($query) => $query->where('department_id', $selectedDepartmentId))
                ->when($selectedCourseId !== null, fn ($query) => $query->where('course_id', $selectedCourseId))
                ->where(function ($query) use ($studentSearch): void {
                    $query->where('name', 'like', "{$studentSearch}%")
                        ->orWhere('email', 'like', "{$studentSearch}%")
                        ->orWhere('student_id_number', 'like', "{$studentSearch}%");
                })
                ->with(['department:id,name', 'course:id,name'])
                ->orderBy('name')
                ->limit(25)
                ->get();
        }
        $dashboardDepartments = $selectedDepartmentId === null
            ? $departments
            : $departments->where('id', $selectedDepartmentId)->values();
        $departmentIds = $dashboardDepartments->pluck('id');
        $totalDepartments = $dashboardDepartments->count();
        $totalAdmins = User::query()
            ->where('role', 'admin')
            ->when(
                $selectedDepartmentId !== null,
                fn ($query) => $query->where('department_id', $selectedDepartmentId),
            )
            ->when($selectedCourseId !== null, fn ($query) => $query->where(function ($query) use ($selectedCourseId): void {
                $query->where('course_id', $selectedCourseId)
                    ->orWhereHas('questionBankItems', fn ($query) => $query->where('course_id', $selectedCourseId));
            }))
            ->count();
        $totalStudents = (int) $dashboardDepartments->sum('users_count');
        $monthStart = $periodEnd->startOfMonth()->subMonths(11);
        $chartLabels = collect(range(0, 11))
            ->map(fn (int $month): string => $monthStart->addMonths($month)->format('M Y'))
            ->all();
        $registrationMonths = User::query()
            ->where('role', 'student')
            ->when($selectedCourseId !== null, fn ($query) => $query->where('course_id', $selectedCourseId))
            ->whereBetween('created_at', [$monthStart, $periodEnd])
            ->get(['department_id', 'course_id', 'created_at'])
            ->groupBy('department_id')
            ->map(fn ($registrations) => $registrations->groupBy(
                fn (User $student): string => $student->created_at->format('Y-m')
            ));
        $fallbackColors = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#0891b2', '#be123c'];
        $departmentDatasets = $dashboardDepartments
            ->values()
            ->map(function (Department $department, int $index) use ($monthStart, $registrationMonths, $fallbackColors, $selectedCourse): array {
                $departmentColor = $department->color_theme;
                if (! is_string($departmentColor) || preg_match('/^#[0-9a-fA-F]{6}$/', $departmentColor) !== 1) {
                    $departmentColor = $fallbackColors[$index % count($fallbackColors)];
                }
                $monthlyRegistrations = $registrationMonths[$department->id] ?? collect();

                return [
                    'label' => $selectedCourse?->name ?? $department->name,
                    'data' => collect(range(0, 11))
                        ->map(fn (int $month): int => ($monthlyRegistrations[$monthStart->addMonths($month)->format('Y-m')] ?? collect())->count())
                        ->all(),
                    'borderColor' => $departmentColor,
                    'backgroundColor' => $departmentColor,
                    'tension' => 0.25,
                    'fill' => false,
                    'spanGaps' => false,
                ];
            })
            ->all();

        $resultsForPeriod = function (CarbonImmutable $start, CarbonImmutable $end) use ($selectedCourseId) {
            return Result::query()
                ->whereBetween('created_at', [$start, $end])
                ->whereHas('test', fn ($query) => $query
                    ->whereNotNull('department_id')
                    ->when($selectedCourseId !== null, fn ($query) => $query->where('course_id', $selectedCourseId)))
                ->whereHas('user', fn ($query) => $query
                    ->where('role', 'student')
                    ->whereNotNull('department_id')
                    ->when($selectedCourseId !== null, fn ($query) => $query->where('course_id', $selectedCourseId)))
                ->with(['test:id,department_id,course_id,is_general', 'user:id,department_id,course_id'])
                ->latest()
                ->get(['id', 'user_id', 'test_id', 'score', 'total_questions', 'created_at']);
        };
        $currentResults = $resultsForPeriod($periodStart, $periodEnd);
        $matchingDepartmentResults = fn ($results) => $results->filter(
            fn (Result $result): bool => $result->user->department_id === $result->test->department_id
        );
        $currentDepartmentResults = $matchingDepartmentResults($currentResults);
        $generalResults = fn ($results) => $results->filter(fn (Result $result): bool => $result->test->is_general);
        $currentGeneralResults = $generalResults($currentDepartmentResults);
        $activeStudentsByDepartment = [];
        foreach ($currentDepartmentResults as $result) {
            $activeStudentsByDepartment[$result->test->department_id][$result->user_id] = true;
        }
        $activeStudySessions = StudySession::query()
            ->whereBetween('started_at', [$periodStart, $periodEnd])
            ->where('active_seconds', '>', 0)
            ->whereHas('student', fn ($query) => $query
                ->where('role', 'student')
                ->whereNotNull('department_id')
                ->when($selectedCourseId !== null, fn ($query) => $query->where('course_id', $selectedCourseId)))
            ->with('student:id,department_id,course_id')
            ->get(['id', 'user_id', 'started_at', 'active_seconds']);
        foreach ($activeStudySessions as $activeStudySession) {
            if ($activeStudySession->student !== null) {
                $activeStudentsByDepartment[$activeStudySession->student->department_id][$activeStudySession->user_id] = true;
            }
        }
        $scoreForResult = fn (Result $result): int => $this->resultPercentage($result);
        $latestPerDepartmentStudent = fn ($results) => $results
            ->groupBy(fn (Result $result): string => $result->test->department_id.':'.$result->user_id)
            ->map(fn ($studentResults) => $studentResults->first());
        $currentGeneralStudentResults = $latestPerDepartmentStudent($currentGeneralResults);
        $departmentStudentTotal = (int) $dashboardDepartments->sum('users_count');
        $activeStudentCount = (int) $dashboardDepartments->sum(
            fn (Department $department): int => count($activeStudentsByDepartment[$department->id] ?? [])
        );
        $activeParticipationRate = $departmentStudentTotal > 0
            ? (int) round(($activeStudentCount / $departmentStudentTotal) * 100)
            : null;
        $departmentAnalytics = $dashboardDepartments->map(function (Department $department, int $index) use (
            $currentGeneralStudentResults,
            $activeStudentsByDepartment,
            $scoreForResult,
            $fallbackColors,
            $selectedCourse,
        ): array {
            $currentScores = $currentGeneralStudentResults
                ->filter(fn (Result $result): bool => $result->test->department_id === $department->id)
                ->map($scoreForResult);
            $averageScore = $currentScores->isNotEmpty() ? (int) round($currentScores->avg()) : null;

            $color = $department->color_theme;
            if (! is_string($color) || preg_match('/^#[0-9a-fA-F]{6}$/', $color) !== 1) {
                $color = $fallbackColors[$index % count($fallbackColors)];
            }

            return [
                'id' => $department->id,
                'name' => $selectedCourse?->name ?? $department->name,
                'color' => $color,
                'students' => $department->users_count,
                'active_students' => count($activeStudentsByDepartment[$department->id] ?? []),
                'participants' => $currentScores->count(),
                'average_score' => $averageScore,
                'pass_rate' => $currentScores->isNotEmpty()
                    ? (int) round(($currentScores->filter(fn (int $score): bool => $score >= 75)->count() / $currentScores->count()) * 100)
                    : null,
            ];
        })->values();
        $departmentScoreDatasets = [[
            'label' => 'Average general-test score',
            'data' => $departmentAnalytics->pluck('average_score')->all(),
            'backgroundColor' => $departmentAnalytics->pluck('color')->all(),
            'borderRadius' => 6,
        ]];

        return view('super-admin.dashboard', compact(
            'totalDepartments',
            'totalAdmins',
            'totalStudents',
            'departments',
            'courses',
            'selectedCourse',
            'selectedCourseId',
            'selectedDepartment',
            'selectedDepartmentId',
            'studentSearch',
            'matchingStudents',
            'chartLabels',
            'departmentDatasets',
            'periodDays',
            'period',
            'periodLabel',
            'periodStartDate',
            'periodEndDate',
            'activeParticipationRate',
            'activeStudentCount',
            'departmentStudentTotal',
            'departmentAnalytics',
            'departmentScoreDatasets',
        ));
    }

    public function profile(): View
    {
        return view('admin.profile.index', [
            'profileUser' => auth()->user(),
            'profileRoute' => 'super-admin.profile.update',
            'passwordRoute' => 'super-admin.profile.password.update',
        ]);
    }

    public function markNotificationRead(Request $request, string $notification): RedirectResponse
    {
        $adminNotification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();
        $adminNotification->markAsRead();

        return back();
    }

    public function leaderboard(Request $request, LeaderboardPointsService $leaderboardPointsService)
    {
        $departments = Department::orderBy('name')->get();
        $requestedDepartmentId = $request->integer('department_id');
        $selectedDepartment = $departments->firstWhere('id', $requestedDepartmentId)
            ?? $departments->first();
        $selectedDepartmentId = (int) ($selectedDepartment?->id ?? 0);
        $leaders = collect();

        if ($selectedDepartment) {
            $students = User::where('role', 'student')
                ->where('department_id', $selectedDepartment->id)
                ->get();

            foreach ($students as $student) {
                $leaderboardPointsService->recalculateFor($student);
                $student->achievements()
                    ->where('required_points', '>', $student->total_points)
                    ->detach();
            }

            $leaders = User::where('role', 'student')
                ->where('department_id', $selectedDepartment->id)
                ->with('department')
                ->orderByDesc('total_points')
                ->orderBy('name')
                ->orderBy('id')
                ->take(10)
                ->get();
        }

        return view('super-admin.leaderboard.index', compact(
            'departments',
            'selectedDepartment',
            'selectedDepartmentId',
            'leaders',
        ));
    }

    private function resultPercentage(Result $result): int
    {
        $totalQuestions = (int) $result->total_questions;
        if ($totalQuestions === 0) {
            $answers = $result->answers;
            if (is_string($answers)) {
                $answers = json_decode($answers, true);
            }

            $totalQuestions = is_array($answers) ? count($answers) : 0;
        }

        return $totalQuestions > 0 ? (int) round(($result->score / $totalQuestions) * 100) : 0;
    }

    public function deleteAdmin(User $admin)
    {
        if ($admin->role === 'admin') {
            $admin->delete();

            return back()->with('success', 'Admin deleted successfully.');
        }

        return back()->withErrors(['error' => 'Invalid user target.']);
    }

    public function storeCourse(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department->courses()->create([
            'name' => $request->name,
        ]);

        return back()->with('success', 'Course added to '.$department->name);
    }

    public function deleteCourse(Course $course)
    {
        $course->delete();

        return back()->with('success', 'Course removed.');
    }
}
