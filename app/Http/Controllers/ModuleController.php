<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMathPdfJob;
use App\Models\Achievement;
use App\Models\Course;
use App\Models\Department;
use App\Models\Module;
use App\Models\Result;
use App\Models\StudentNotification;
use App\Models\StudySession;
use App\Models\Subtopic;
use App\Models\Test;
use App\Models\User;
use App\Notifications\SubtopicNeedsRevisionNotification;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ]);

        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $selectedDepartmentId = isset($validated['department_id'])
            ? (int) $validated['department_id']
            : null;
        $selectedCourseId = isset($validated['course_id'])
            ? (int) $validated['course_id']
            : null;

        abort_if($selectedCourseId !== null && $selectedDepartmentId === null, 404);

        $courses = $selectedDepartmentId === null
            ? collect()
            : Course::query()
                ->where('department_id', $selectedDepartmentId)
                ->orderBy('name')
                ->get(['id', 'name', 'department_id']);

        if ($selectedCourseId !== null) {
            abort_unless($courses->contains('id', $selectedCourseId), 404);
        }

        $modules = Module::query()
            ->with([
                'department:id,name',
                'course:id,name',
                'creator:id,name',
            ])
            ->withCount('subtopics')
            ->when($selectedDepartmentId !== null, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->when($selectedCourseId !== null, fn ($query) => $query->where('course_id', $selectedCourseId))
            ->latest()
            ->get();

        $topicCount = (int) Subtopic::query()
            ->whereIn('module_id', $modules->modelKeys())
            ->count();
        $flaggedTopicCount = (int) Subtopic::query()
            ->whereIn('module_id', $modules->modelKeys())
            ->where('needs_revision', true)
            ->count();

        return view('super-admin.modules.index', compact(
            'departments',
            'courses',
            'modules',
            'selectedDepartmentId',
            'selectedCourseId',
            'topicCount',
            'flaggedTopicCount',
        ));
    }

    public function adminShow(Module $module): View
    {
        $module->load([
            'department:id,name',
            'course:id,name',
            'creator:id,name',
            'subtopics' => fn ($query) => $query->orderBy('title'),
        ]);
        $subtopicIds = $module->subtopics->modelKeys();
        $students = $module->course_id === null
            ? collect()
            : User::query()
                ->where('role', 'student')
                ->where('department_id', $module->department_id)
                ->where('course_id', $module->course_id)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'student_id_number', 'profile_picture_path', 'total_points', 'last_active_at']);

        $attemptStatsByStudentTopic = collect();
        $studyTimeStatsByStudentTopic = collect();

        if ($students->isNotEmpty() && $subtopicIds !== []) {
            $attemptStatsByStudentTopic = Result::query()
                ->join('tests', 'tests.id', '=', 'results.test_id')
                ->whereIn('results.user_id', $students->modelKeys())
                ->whereIn('tests.subtopic_id', $subtopicIds)
                ->where('tests.department_id', $module->department_id)
                ->where('tests.course_id', $module->course_id)
                ->where('tests.is_general', false)
                ->where('results.total_questions', '>', 0)
                ->selectRaw('results.user_id, tests.subtopic_id')
                ->selectRaw('SUM(ROUND(results.score * 50.0 / results.total_questions, 2)) as total_points')
                ->selectRaw('SUM(results.score * 100.0 / results.total_questions) as score_percentage_sum')
                ->selectRaw('MAX(results.score * 100.0 / results.total_questions) as best_score')
                ->selectRaw('COUNT(*) as quiz_attempts')
                ->selectRaw('MAX(results.created_at) as last_quiz_at')
                ->groupBy('results.user_id', 'tests.subtopic_id')
                ->get()
                ->groupBy('user_id')
                ->map(fn (Collection $topicStats): Collection => $topicStats->keyBy('subtopic_id'));

            $studyTimeStatsByStudentTopic = StudySession::query()
                ->where('module_id', $module->id)
                ->whereIn('user_id', $students->modelKeys())
                ->whereIn('subtopic_id', $subtopicIds)
                ->selectRaw('user_id, subtopic_id, SUM(active_seconds) as active_seconds')
                ->selectRaw('MAX(last_heartbeat_at) as last_session_at')
                ->groupBy('user_id', 'subtopic_id')
                ->get()
                ->groupBy('user_id')
                ->map(fn (Collection $topicStats): Collection => $topicStats->keyBy('subtopic_id'));
        }

        $studentRows = $students->map(function (User $student) use (
            $attemptStatsByStudentTopic,
            $studyTimeStatsByStudentTopic,
            $module,
        ): array {
            $attemptsByTopic = $attemptStatsByStudentTopic->get($student->id, collect());
            $studyTimeByTopic = $studyTimeStatsByStudentTopic->get($student->id, collect());
            $totalPoints = 0.0;
            $scorePercentageSum = 0.0;
            $quizAttempts = 0;
            $verifiedSeconds = 0;
            $lastActiveAt = null;
            $topicStats = [];

            foreach ($module->subtopics as $topic) {
                $attempts = $attemptsByTopic->get($topic->id);
                $study = $studyTimeByTopic->get($topic->id);
                $attemptCount = (int) ($attempts?->quiz_attempts ?? 0);
                $studySeconds = (int) ($study?->active_seconds ?? 0);
                $topicVerifiedSeconds = $attemptCount > 0
                    ? min($studySeconds, StudySession::TOPIC_STUDY_CAP_SECONDS)
                    : 0;
                $bestScore = $attempts === null ? null : round((float) $attempts->best_score, 2);
                $completed = $topicVerifiedSeconds >= 300 && $bestScore !== null && $bestScore >= 75;
                $topicLastActiveAt = collect([
                    $attempts?->last_quiz_at,
                    $study?->last_session_at,
                ])->filter()->max();
                $topicLastActiveAt = $topicLastActiveAt === null
                    ? null
                    : CarbonImmutable::parse($topicLastActiveAt)->toIso8601String();

                $topicStats[$topic->id] = [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'points' => round((float) ($attempts?->total_points ?? 0), 2),
                    'score_percentage_sum' => (float) ($attempts?->score_percentage_sum ?? 0),
                    'best_score' => $bestScore,
                    'quiz_attempts' => $attemptCount,
                    'study_seconds' => min($studySeconds, StudySession::TOPIC_STUDY_CAP_SECONDS),
                    'verified_seconds' => $topicVerifiedSeconds,
                    'completed' => $completed,
                    'status' => $completed
                        ? 'Completed'
                        : ($studySeconds > 0 || $attemptCount > 0 ? 'In Progress' : 'Not Started'),
                    'last_active_at' => $topicLastActiveAt,
                ];

                $totalPoints += (float) ($attempts?->total_points ?? 0);
                $scorePercentageSum += (float) ($attempts?->score_percentage_sum ?? 0);
                $quizAttempts += $attemptCount;
                $verifiedSeconds += $topicVerifiedSeconds;
                if ($topicLastActiveAt !== null && ($lastActiveAt === null || $topicLastActiveAt > $lastActiveAt)) {
                    $lastActiveAt = $topicLastActiveAt;
                }
            }

            $completedTopics = collect($topicStats)->where('completed', true)->count();
            $isInactive = $lastActiveAt === null
                || CarbonImmutable::parse($lastActiveAt)->lt(now()->subDays(14));

            return [
                'id' => $student->id,
                'name' => $student->name,
                'average_score' => $quizAttempts > 0 ? round($scorePercentageSum / $quizAttempts, 2) : null,
                'total_points' => round($totalPoints, 2),
                'quiz_attempts' => $quizAttempts,
                'verified_seconds' => $verifiedSeconds,
                'topics_completed' => $completedTopics,
                'topic_count' => count($topicStats),
                'last_active_at' => $lastActiveAt,
                'status' => $isInactive
                    ? 'Inactive'
                    : ($completedTopics < count($topicStats) ? 'Needs Review' : 'On Track'),
                'topic_stats' => $topicStats,
                'activity_url' => route('super-admin.modules.students.activity', [
                    'module' => $module,
                    'student' => $student,
                ]),
                'overview_url' => route('super-admin.modules.students.overview', [
                    'module' => $module,
                    'student' => $student,
                ]),
                'notice_url' => route('super-admin.modules.students.notifications.store', [
                    'module' => $module,
                    'student' => $student,
                ]),
            ];
        });

        $leaderboard = $studentRows
            ->sort(fn (array $first, array $second): int => ($second['total_points'] <=> $first['total_points'])
                ?: strcasecmp($first['name'], $second['name']))
            ->values()
            ->map(fn (array $student, int $index): array => $student + ['rank' => $index + 1]);
        $reviewStudents = $studentRows
            ->sort(fn (array $first, array $second): int => ($second['verified_seconds'] <=> $first['verified_seconds'])
                ?: strcasecmp($first['name'], $second['name']))
            ->values()
            ->map(fn (array $student, int $index): array => $student + ['rank' => $index + 1]);

        return view('super-admin.modules.show', compact('module', 'students', 'leaderboard', 'reviewStudents'));
    }

    public function adminFlagSubtopic(Request $request, Subtopic $subtopic): RedirectResponse
    {
        $validated = $request->validate([
            'needs_revision' => ['required', 'boolean'],
        ]);

        $subtopic->loadMissing('module.creator');
        $needsRevision = (bool) $validated['needs_revision'];
        $subtopic->update(['needs_revision' => $needsRevision]);

        if ($needsRevision) {
            $recipient = $subtopic->creator
                ?? $subtopic->module?->creator;

            if ($recipient === null) {
                $recipient = User::query()
                    ->where('role', 'admin')
                    ->where('department_id', $subtopic->module?->department_id)
                    ->where(function ($query) use ($subtopic): void {
                        $query->where('course_id', $subtopic->module?->course_id)
                            ->orWhereHas('handledCourses', fn ($courseQuery) => $courseQuery
                                ->where('courses.id', $subtopic->module?->course_id));
                    })
                    ->orderBy('name')
                    ->first();
            }

            if ($recipient !== null) {
                $recipient->notify(new SubtopicNeedsRevisionNotification($subtopic, $request->user()));
            }
        }

        return back()->with(
            'success',
            $needsRevision
                ? 'Topic flagged for revision. The responsible faculty has been notified.'
                : 'Topic revision flag removed.',
        );
    }

    public function index(Request $request): View
    {
        $faculty = $request->user();
        $departmentId = $faculty->department_id;
        $courses = Course::query()
            ->where('department_id', $departmentId)
            ->orderBy('name')
            ->get();
        $selectedCourseId = $request->integer('course_id') ?: ($courses->count() === 1 ? $courses->first()->id : null);

        if ($selectedCourseId !== null && ! $courses->contains('id', $selectedCourseId)) {
            abort(404);
        }

        $modules = Module::query()
            ->with('course:id,name')
            ->withCount('subtopics')
            ->forFaculty($faculty)
            ->when($selectedCourseId !== null, fn ($query) => $query->where('course_id', $selectedCourseId))
            ->latest()
            ->get();

        return view('admin.modules.index', compact('modules', 'courses', 'selectedCourseId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = $request->user();
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Validate image upload up to 5MB
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);
        $course = Course::query()
            ->whereKey($validated['course_id'])
            ->where('department_id', $admin->department_id)
            ->firstOrFail();

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('modules/logos', 'public');
        }

        Module::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'logo_path' => $logoPath,
            'department_id' => $admin->department_id,
            'course_id' => $course->id,
            'created_by' => $admin->id,
        ]);

        return redirect()
            ->route('admin.modules.index', ['course_id' => $course->id])
            ->with('success', 'Module created successfully.');
    }

    public function show(Module $module): View
    {
        $this->ensureModuleBelongsToAuthenticatedAdmin($module);

        $module->load('subtopics:id,module_id,title');
        $subtopicIds = $module->subtopics->modelKeys();
        $students = $module->course_id === null
            ? collect()
            : User::query()
                ->where('role', 'student')
                ->where('department_id', $module->department_id)
                ->where('course_id', $module->course_id)
                ->orderBy('name')
                ->get(['id', 'name']);
        $attemptStatsByStudentTopic = collect();
        $studyTimeStatsByStudentTopic = collect();

        if ($students->isNotEmpty() && $subtopicIds !== []) {
            $attemptStatsByStudentTopic = Result::query()
                ->join('tests', 'tests.id', '=', 'results.test_id')
                ->whereIn('results.user_id', $students->modelKeys())
                ->whereIn('tests.subtopic_id', $subtopicIds)
                ->where('tests.department_id', $module->department_id)
                ->where('tests.course_id', $module->course_id)
                ->where('tests.is_general', false)
                ->where('results.total_questions', '>', 0)
                ->selectRaw('results.user_id, tests.subtopic_id')
                ->selectRaw('SUM(ROUND(results.score * 50.0 / results.total_questions, 2)) as total_points')
                ->selectRaw('SUM(results.score * 100.0 / results.total_questions) as score_percentage_sum')
                ->selectRaw('MAX(results.score * 100.0 / results.total_questions) as best_score')
                ->selectRaw('COUNT(*) as quiz_attempts')
                ->selectRaw('MAX(results.created_at) as last_quiz_at')
                ->groupBy('results.user_id', 'tests.subtopic_id')
                ->get()
                ->groupBy('user_id')
                ->map(fn (Collection $topicStats): Collection => $topicStats->keyBy('subtopic_id'));

            $studyTimeStatsByStudentTopic = StudySession::query()
                ->where('module_id', $module->id)
                ->whereIn('user_id', $students->modelKeys())
                ->whereIn('subtopic_id', $subtopicIds)
                ->selectRaw('user_id, subtopic_id, SUM(active_seconds) as active_seconds')
                ->selectRaw('MAX(last_heartbeat_at) as last_session_at')
                ->groupBy('user_id', 'subtopic_id')
                ->get()
                ->groupBy('user_id')
                ->map(fn (Collection $topicStats): Collection => $topicStats->keyBy('subtopic_id'));
        }

        $studentRows = $students->map(function (User $student) use (
            $attemptStatsByStudentTopic,
            $studyTimeStatsByStudentTopic,
            $module,
        ): array {
            $attemptsByTopic = $attemptStatsByStudentTopic->get($student->id, collect());
            $studyTimeByTopic = $studyTimeStatsByStudentTopic->get($student->id, collect());
            $totalPoints = 0.0;
            $scorePercentageSum = 0.0;
            $quizAttempts = 0;
            $verifiedSeconds = 0;
            $lastActiveAt = null;
            $topicStats = [];

            foreach ($module->subtopics as $topic) {
                $attempts = $attemptsByTopic->get($topic->id);
                $study = $studyTimeByTopic->get($topic->id);
                $attemptCount = (int) ($attempts?->quiz_attempts ?? 0);
                $studySeconds = (int) ($study?->active_seconds ?? 0);
                $topicVerifiedSeconds = $attemptCount > 0
                    ? min($studySeconds, StudySession::TOPIC_STUDY_CAP_SECONDS)
                    : 0;
                $bestScore = $attempts === null ? null : round((float) $attempts->best_score, 2);
                $completed = $topicVerifiedSeconds >= 300 && $bestScore !== null && $bestScore >= 75;
                $topicLastActiveAt = collect([
                    $attempts?->last_quiz_at,
                    $study?->last_session_at,
                ])->filter()->max();
                $topicLastActiveAt = $topicLastActiveAt === null
                    ? null
                    : CarbonImmutable::parse($topicLastActiveAt)->toIso8601String();

                $topicStats[$topic->id] = [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'points' => round((float) ($attempts?->total_points ?? 0), 2),
                    'score_percentage_sum' => (float) ($attempts?->score_percentage_sum ?? 0),
                    'best_score' => $bestScore,
                    'quiz_attempts' => $attemptCount,
                    'study_seconds' => min($studySeconds, StudySession::TOPIC_STUDY_CAP_SECONDS),
                    'verified_seconds' => $topicVerifiedSeconds,
                    'completed' => $completed,
                    'status' => $completed
                        ? 'Completed'
                        : ($studySeconds > 0 || $attemptCount > 0 ? 'In Progress' : 'Not Started'),
                    'last_active_at' => $topicLastActiveAt,
                ];

                $totalPoints += (float) ($attempts?->total_points ?? 0);
                $scorePercentageSum += (float) ($attempts?->score_percentage_sum ?? 0);
                $quizAttempts += $attemptCount;
                $verifiedSeconds += $topicVerifiedSeconds;
                if ($topicLastActiveAt !== null && ($lastActiveAt === null || $topicLastActiveAt > $lastActiveAt)) {
                    $lastActiveAt = $topicLastActiveAt;
                }
            }

            $completedTopics = collect($topicStats)->where('completed', true)->count();
            $isInactive = $lastActiveAt === null
                || CarbonImmutable::parse($lastActiveAt)->lt(now()->subDays(14));

            return [
                'id' => $student->id,
                'name' => $student->name,
                'average_score' => $quizAttempts > 0 ? round($scorePercentageSum / $quizAttempts, 2) : null,
                'score_percentage_sum' => $scorePercentageSum,
                'total_points' => round($totalPoints, 2),
                'quiz_attempts' => $quizAttempts,
                'verified_seconds' => $verifiedSeconds,
                'topics_completed' => $completedTopics,
                'topic_count' => count($topicStats),
                'last_active_at' => $lastActiveAt,
                'status' => $isInactive
                    ? 'Inactive'
                    : ($completedTopics < count($topicStats) ? 'Needs Review' : 'On Track'),
                'topic_stats' => $topicStats,
                'activity_url' => route('admin.modules.students.activity', [
                    'module' => $module,
                    'student' => $student,
                ]),
                'overview_url' => route('admin.modules.students.overview', [
                    'module' => $module,
                    'student' => $student,
                ]),
                'notice_url' => route('admin.modules.students.notifications.store', [
                    'module' => $module,
                    'student' => $student,
                ]),
            ];
        });

        $leaderboard = $studentRows
            ->sort(fn (array $first, array $second): int => ($second['total_points'] <=> $first['total_points'])
                ?: strcasecmp($first['name'], $second['name']))
            ->values()
            ->map(fn (array $student, int $index): array => $student + ['rank' => $index + 1]);
        $reviewStudents = $studentRows
            ->sort(fn (array $first, array $second): int => ($second['verified_seconds'] <=> $first['verified_seconds'])
                ?: strcasecmp($first['name'], $second['name']))
            ->values()
            ->map(fn (array $student, int $index): array => $student + ['rank' => $index + 1]);

        return view('admin.modules.show', compact('module', 'leaderboard', 'reviewStudents'));
    }

    public function sendStudentNotice(Request $request, Module $module, User $student): RedirectResponse
    {
        $this->ensureModuleBelongsToAuthenticatedAdmin($module);
        $this->ensureStudentIsEnrolledInModule($student, $module);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        StudentNotification::create([
            'subject' => $module->title,
            'message' => $validated['message'],
            'student_id' => $student->id,
            'admin_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Notice sent to '.$student->name.'.');
    }

    private function ensureStudentIsEnrolledInModule(User $student, Module $module): void
    {
        abort_unless(
            $student->role === 'student'
                && $student->department_id === $module->department_id
                && $module->course_id !== null
                && $student->course_id === $module->course_id,
            404,
        );
    }

    public function destroy(Module $module)
    {
        $this->ensureModuleBelongsToAuthenticatedAdmin($module);

        // 1. Delete the module's logo file if it exists
        if ($module->logo_path && Storage::disk('public')->exists($module->logo_path)) {
            Storage::disk('public')->delete($module->logo_path);
        }

        // 2. Loop through all subtopics and delete their physical files
        foreach ($module->subtopics as $subtopic) {
            if ($subtopic->file_path && Storage::disk('public')->exists($subtopic->file_path)) {
                Storage::disk('public')->delete($subtopic->file_path);
            }
        }

        // 3. Delete the subtopic records from the database
        $module->subtopics()->delete();

        // 4. Finally, delete the module itself
        $module->delete();

        return redirect()->route('admin.modules.index')->with('success', 'Module and all its topics were deleted successfully.');
    }

    public function storeSubtopic(Request $request, Module $module)
    {
        $this->ensureModuleBelongsToAuthenticatedAdmin($module);

        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,zip|max:76800',
        ]);

        $filePath = $request->file('file')->store('modules/subtopics', 'public');

        $subtopic = $module->subtopics()->create([
            'title' => $request->title,
            'file_path' => $filePath,
            'created_by' => $request->user()->id,
            'needs_revision' => false,
            'text_extraction_status' => $this->isPdf($filePath)
                ? Subtopic::TEXT_EXTRACTION_PENDING
                : Subtopic::TEXT_EXTRACTION_NOT_REQUIRED,
        ]);

        if ($this->isPdf($filePath)) {
            ProcessMathPdfJob::dispatch($subtopic->id, $filePath);
        }

        return redirect()->route('admin.modules.show', $module)->with('success', 'Topic added successfully.');
    }

    public function destroySubtopic(Subtopic $subtopic)
    {
        $this->ensureModuleBelongsToAuthenticatedAdmin($subtopic->module);

        if ($subtopic->file_path && Storage::disk('public')->exists($subtopic->file_path)) {
            Storage::disk('public')->delete($subtopic->file_path);
        }

        DB::transaction(function () use ($subtopic): void {
            $tests = Test::query()
                ->where('course_id', $subtopic->module->course_id)
                ->where(function ($query) use ($subtopic) {
                    $query->where('topic', 'like', '%'.$subtopic->title.'%')
                        ->orWhere('title', 'like', '%'.$subtopic->title.'%');
                })
                ->get();

            $affectedUserIds = [];
            foreach ($tests as $test) {
                $affectedUserIds = array_merge($affectedUserIds, Result::where('test_id', $test->id)->pluck('user_id')->all());
                $test->delete();
            }

            $subtopic->delete();

            $this->recalculateStudentPoints(
                array_unique($affectedUserIds),
                $subtopic->module->department_id,
                (int) $subtopic->module->course_id,
            );
        });

        return redirect()->back()->with('success', 'Topic deleted successfully.');
    }

    /**
     * Update the specified subtopic and handle optional file replacement.
     */
    public function updateSubtopic(Request $request, Subtopic $subtopic)
    {
        $this->ensureModuleBelongsToAuthenticatedAdmin($subtopic->module);

        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,zip|max:76800',
        ]);

        $subtopic->title = $request->title;
        $subtopic->needs_revision = false;
        $oldFilePath = $subtopic->file_path;
        $newFilePath = null;

        // If a new file is uploaded, replace the old one
        if ($request->hasFile('file')) {
            $newFilePath = $request->file('file')->store('modules/subtopics', 'public');
            $subtopic->file_path = $newFilePath;
            $subtopic->extracted_text = null;
            $subtopic->text_extraction_status = $this->isPdf($newFilePath)
                ? Subtopic::TEXT_EXTRACTION_PENDING
                : Subtopic::TEXT_EXTRACTION_NOT_REQUIRED;
        }

        $subtopic->save();

        if ($newFilePath !== null) {
            if ($oldFilePath && Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }

            if ($this->isPdf($newFilePath)) {
                ProcessMathPdfJob::dispatch($subtopic->id, $newFilePath);
            }
        }

        return redirect()->back()->with('success', 'Topic updated successfully.');
    }

    private function isPdf(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf';
    }

    private function ensureModuleBelongsToAuthenticatedAdmin(Module $module): void
    {
        $user = auth()->user();

        abort_unless(
            $user->role === 'super-admin'
                || ($user->role === 'admin' && $module->department_id === $user->department_id),
            404,
        );
    }

    /**
     * Rebuild point totals from the remaining quiz results so deleted content
     * cannot leave stale leaderboard or achievement totals behind.
     *
     * @param  array<int, int>  $userIds
     */
    private function recalculateStudentPoints(array $userIds, int $departmentId, int $courseId): void
    {
        if ($userIds === []) {
            return;
        }

        $subtopics = Subtopic::whereHas('module', fn ($query) => $query
            ->where('department_id', $departmentId)
            ->where('course_id', $courseId))
            ->get();

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            $totalPoints = 0;

            foreach ($subtopics as $subtopic) {
                $totalPoints += $subtopic->earnedPointsForUser($user->id);
            }

            $user->update(['total_points' => $totalPoints]);
            $user->achievements()
                ->where('required_points', '>', $totalPoints)
                ->detach();
        }
    }
}
