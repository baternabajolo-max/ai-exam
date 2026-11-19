<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\Result;
use App\Models\StudySession;
use App\Models\User;
use App\Services\LeaderboardPointsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function monitoring(Request $request): View
    {
        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ]);
        $selectedCourseId = isset($validated['course_id']) ? (int) $validated['course_id'] : null;
        $selectedCourse = $selectedCourseId === null
            ? null
            : Course::query()->findOrFail($selectedCourseId);
        $courses = Course::query()
            ->with('department:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);
        $faculties = User::query()
            ->where('role', 'admin')
            ->when($selectedCourseId !== null, fn ($query) => $query->where(function ($query) use ($selectedCourseId): void {
                $query->where('course_id', $selectedCourseId)
                    ->orWhereHas('questionBankItems', fn ($query) => $query->where('course_id', $selectedCourseId));
            }))
            ->with([
                'department:id,name',
                'course:id,name',
            ])
            ->withCount([
                'questionBankItems' => fn ($query) => $query->when(
                    $selectedCourseId !== null,
                    fn ($query) => $query->where('course_id', $selectedCourseId),
                ),
            ])
            ->orderBy('name')
            ->get();
        $recentAssessments = Result::query()
            ->whereHas('test', fn ($query) => $query->where('is_scheduled_exam', true))
            ->when($selectedCourseId !== null, fn ($query) => $query->whereHas(
                'test',
                fn ($query) => $query->where('course_id', $selectedCourseId),
            ))
            ->with([
                'user:id,name,course_id',
                'user.course:id,name',
                'test:id,title,course_id',
                'test.course:id,name',
            ])
            ->latest()
            ->take(25)
            ->get();

        return view('super-admin.monitoring.index', compact(
            'faculties',
            'recentAssessments',
            'courses',
            'selectedCourseId',
            'selectedCourse',
        ));
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
