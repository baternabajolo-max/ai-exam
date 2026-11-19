<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Result;
use App\Models\StudySession;
use App\Models\Subtopic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->activityForStudent($request, $request->user());
    }

    public function adminIndex(Request $request, User $student): JsonResponse
    {
        $this->ensureAdminCanViewStudent($request, $student);

        return $this->activityForStudent($request, $student);
    }

    public function adminModuleStudentActivity(Request $request, Module $module, User $student): JsonResponse
    {
        $this->ensureAdminCanViewStudent($request, $student);
        abort_unless(
            $module->department_id === $request->user()->department_id
                && $module->course_id !== null
                && $student->course_id === $module->course_id,
            404,
        );

        $validated = $request->validate([
            'month' => ['required_with:date', 'nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'subtopic_id' => ['nullable', 'integer'],
        ]);
        $selectedSubtopicId = isset($validated['subtopic_id']) ? (int) $validated['subtopic_id'] : null;
        if ($selectedSubtopicId !== null) {
            $module->subtopics()->whereKey($selectedSubtopicId)->firstOrFail();
        }
        $registeredMonth = $student->created_at->startOfMonth();
        $currentMonth = now()->startOfMonth();
        $selectedMonth = isset($validated['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $validated['month'])
            : CarbonImmutable::instance($currentMonth);

        abort_unless(
            $selectedMonth->gte($registeredMonth)
                && $selectedMonth->lte($currentMonth),
            404,
        );
        $selectedDate = isset($validated['date'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $validated['date'])
            : null;
        abort_unless(
            $selectedDate === null || $selectedDate->format('Y-m') === $selectedMonth->format('Y-m'),
            404,
        );

        $creditedSecondsByTopic = [];
        $verifiedSessions = StudySession::query()
            ->verifiedByQuiz()
            ->where('user_id', $student->id)
            ->where('module_id', $module->id)
            ->when($selectedSubtopicId !== null, fn ($query) => $query->where('subtopic_id', $selectedSubtopicId))
            ->whereBetween('activity_date', [
                $registeredMonth->startOfDay(),
                $currentMonth->endOfMonth()->endOfDay(),
            ])
            ->selectRaw('activity_date, subtopic_id, SUM(active_seconds) as active_seconds')
            ->groupBy('activity_date', 'subtopic_id')
            ->orderBy('activity_date')
            ->orderBy('subtopic_id')
            ->get();
        $verifiedSessions = $verifiedSessions
            ->map(function (StudySession $session) use (&$creditedSecondsByTopic): StudySession {
                $topicId = (int) $session->subtopic_id;
                $remainingSeconds = max(
                    0,
                    StudySession::TOPIC_STUDY_CAP_SECONDS - ($creditedSecondsByTopic[$topicId] ?? 0),
                );
                $session->active_seconds = min(max(0, (int) $session->active_seconds), $remainingSeconds);
                $creditedSecondsByTopic[$topicId] = ($creditedSecondsByTopic[$topicId] ?? 0) + $session->active_seconds;

                return $session;
            })
            ->filter(fn (StudySession $session): bool => $session->active_seconds > 0)
            ->values();

        $months = [];
        $period = CarbonImmutable::parse($registeredMonth);
        while ($period <= $currentMonth) {
            $months[$period->format('Y-m')] = [
                'month' => $period->format('Y-m'),
                'label' => $period->format('M Y'),
                'active_seconds' => 0,
            ];
            $period = $period->addMonth();
        }

        foreach ($verifiedSessions as $session) {
            $monthKey = CarbonImmutable::parse($session->activity_date)->format('Y-m');
            if (isset($months[$monthKey])) {
                $months[$monthKey]['active_seconds'] += (int) $session->active_seconds;
            }
        }

        $dailyTotals = $verifiedSessions
            ->filter(fn (StudySession $session): bool => CarbonImmutable::parse($session->activity_date)->format('Y-m') === $selectedMonth->format('Y-m'))
            ->groupBy(fn (StudySession $session): string => CarbonImmutable::parse($session->activity_date)->toDateString())
            ->map(fn ($sessions): int => (int) $sessions->sum('active_seconds'));
        $selectedMonthTopicTotals = $verifiedSessions
            ->filter(fn (StudySession $session): bool => CarbonImmutable::parse($session->activity_date)->format('Y-m') === $selectedMonth->format('Y-m'))
            ->groupBy('subtopic_id')
            ->map(fn ($sessions): int => (int) $sessions->sum('active_seconds'));
        $selectedDateTopicTotals = $selectedDate === null
            ? collect()
            : $verifiedSessions
                ->filter(fn (StudySession $session): bool => CarbonImmutable::parse($session->activity_date)->toDateString() === $selectedDate->toDateString())
                ->groupBy('subtopic_id')
                ->map(fn ($sessions): int => (int) $sessions->sum('active_seconds'));
        $allTimeTopicTotals = $verifiedSessions
            ->groupBy('subtopic_id')
            ->map(fn ($sessions): int => (int) $sessions->sum('active_seconds'));
        $topics = Subtopic::query()
            ->where('module_id', $module->id)
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (Subtopic $subtopic): array => [
                'id' => $subtopic->id,
                'title' => $subtopic->title,
                'active_seconds' => $selectedDate === null
                    ? (int) ($selectedMonthTopicTotals[$subtopic->id] ?? 0)
                    : (int) ($selectedDateTopicTotals[$subtopic->id] ?? 0),
                'all_time_seconds' => (int) ($allTimeTopicTotals[$subtopic->id] ?? 0),
            ]);

        return response()->json([
            'module' => ['id' => $module->id, 'title' => $module->title],
            'month' => $selectedMonth->format('Y-m'),
            'subtopic_id' => $selectedSubtopicId,
            'date' => $selectedDate?->toDateString(),
            'months' => array_values($months),
            'calendar' => [
                'label' => $selectedMonth->format('F Y'),
                'first_weekday' => $selectedMonth->startOfMonth()->dayOfWeek,
                'days_in_month' => $selectedMonth->daysInMonth,
                'days' => collect(range(1, $selectedMonth->daysInMonth))
                    ->map(fn (int $day): array => [
                        'day' => $day,
                        'date' => $selectedMonth->startOfMonth()->addDays($day - 1)->toDateString(),
                        'active_seconds' => (int) ($dailyTotals[$selectedMonth->startOfMonth()->addDays($day - 1)->toDateString()] ?? 0),
                    ]),
            ],
            'topics' => $topics,
        ]);
    }

    public function adminModuleStudentOverview(Request $request, Module $module, User $student): JsonResponse
    {
        $this->ensureAdminCanViewStudent($request, $student);
        abort_unless(
            $module->department_id === $request->user()->department_id
                && $module->course_id !== null
                && $student->course_id === $module->course_id,
            404,
        );

        $validated = $request->validate([
            'subtopic_id' => ['nullable', 'integer'],
        ]);
        $topics = $module->subtopics()->orderBy('title')->get(['id', 'title']);
        $selectedSubtopicId = isset($validated['subtopic_id']) ? (int) $validated['subtopic_id'] : null;
        if ($selectedSubtopicId !== null && ! $topics->contains('id', $selectedSubtopicId)) {
            abort(404);
        }
        $topicIds = $topics->modelKeys();
        $sessionsByTopic = StudySession::query()
            ->where('user_id', $student->id)
            ->where('module_id', $module->id)
            ->whereIn('subtopic_id', $topicIds)
            ->selectRaw('subtopic_id, SUM(active_seconds) as active_seconds')
            ->selectRaw('MAX(last_heartbeat_at) as last_session_at')
            ->groupBy('subtopic_id')
            ->get()
            ->keyBy('subtopic_id');
        $attemptsByTopic = Result::query()
            ->whereBelongsTo($student)
            ->where('total_questions', '>', 0)
            ->whereHas('test', fn ($query) => $query
                ->where('department_id', $module->department_id)
                ->where('course_id', $module->course_id)
                ->whereIn('subtopic_id', $topicIds)
                ->where('is_general', false))
            ->with('test:id,subtopic_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'test_id', 'score', 'total_questions', 'created_at'])
            ->groupBy(fn (Result $result): int => $result->test->subtopic_id);

        $topicRows = $topics->map(function (Subtopic $topic) use ($sessionsByTopic, $attemptsByTopic): array {
            $topicSessions = $sessionsByTopic->get($topic->id);
            $topicAttempts = $attemptsByTopic->get($topic->id, collect());
            $studySeconds = min(
                (int) ($topicSessions?->active_seconds ?? 0),
                StudySession::TOPIC_STUDY_CAP_SECONDS,
            );
            $scorePercentages = $topicAttempts
                ->map(fn (Result $result): float => ($result->score / $result->total_questions) * 100);
            $bestScore = $scorePercentages->isNotEmpty() ? round((float) $scorePercentages->max(), 2) : null;
            $verifiedSeconds = $topicAttempts->isNotEmpty() ? $studySeconds : 0;
            $completed = $verifiedSeconds >= 300 && $bestScore !== null && $bestScore >= 75;
            $lastActiveAt = collect([
                $topicSessions?->last_session_at,
                $topicAttempts->max('created_at'),
            ])->filter()->max();

            return [
                'id' => $topic->id,
                'title' => $topic->title,
                'study_seconds' => $studySeconds,
                'verified_seconds' => $verifiedSeconds,
                'quiz_attempts' => $topicAttempts->count(),
                'average_score' => $scorePercentages->isNotEmpty()
                    ? round((float) $scorePercentages->avg(), 2)
                    : null,
                'best_score' => $bestScore,
                'completed' => $completed,
                'status' => $completed
                    ? 'Completed'
                    : ($studySeconds > 0 || $topicAttempts->isNotEmpty() ? 'In Progress' : 'Not Started'),
                'last_active_at' => $lastActiveAt === null
                    ? null
                    : CarbonImmutable::parse($lastActiveAt)->toIso8601String(),
            ];
        });

        $allAttempts = $attemptsByTopic->flatten(1);
        $lastActiveAt = collect([
            $sessionsByTopic->max('last_session_at'),
            $allAttempts->max('created_at'),
        ])->filter()->max();
        $status = $lastActiveAt === null || CarbonImmutable::parse($lastActiveAt)->lt(now()->subDays(14))
            ? 'Inactive'
            : ($topicRows->contains(fn (array $topic): bool => ! $topic['completed']) ? 'Needs Review' : 'On Track');
        $selectedTopic = $selectedSubtopicId === null
            ? null
            : $topicRows->firstWhere('id', $selectedSubtopicId);
        $selectedAttempts = $selectedSubtopicId === null
            ? []
            : $attemptsByTopic->get($selectedSubtopicId, collect())
                ->values()
                ->map(fn (Result $result, int $index): array => [
                    'attempt' => $index + 1,
                    'score' => round(($result->score / $result->total_questions) * 100, 2),
                    'points' => round(($result->score / $result->total_questions) * 50, 2),
                    'created_at' => $result->created_at->toIso8601String(),
                ])
                ->all();

        return response()->json([
            'student' => ['id' => $student->id, 'name' => $student->name],
            'module' => ['id' => $module->id, 'title' => $module->title],
            'status' => $selectedTopic['status'] ?? $status,
            'selected_topic' => $selectedTopic,
            'verified_seconds' => (int) $topicRows->sum('verified_seconds'),
            'topics_completed' => $topicRows->where('completed', true)->count(),
            'topic_count' => $topicRows->count(),
            'average_score' => $allAttempts->isNotEmpty()
                ? round((float) $allAttempts->avg(
                    fn (Result $result): float => ($result->score / $result->total_questions) * 100,
                ), 2)
                : null,
            'quiz_attempts' => $allAttempts->count(),
            'last_active_at' => $lastActiveAt === null
                ? null
                : CarbonImmutable::parse($lastActiveAt)->toIso8601String(),
            'topics' => $topicRows->values(),
            'attempts' => $selectedAttempts,
        ]);
    }

    private function activityForStudent(Request $request, User $student): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required_with:date,module_id', 'date_format:Y-m'],
            'date' => ['required_with:module_id', 'date_format:Y-m-d'],
            'module_id' => ['sometimes', 'integer'],
        ]);

        $registeredMonth = $student->created_at->startOfMonth()->toDateString();
        $currentMonth = now()->startOfMonth()->toDateString();

        if (! isset($validated['month'])) {
            return $this->monthlyActivity($student, $registeredMonth, $currentMonth);
        }

        $month = CarbonImmutable::createFromFormat('!Y-m', $validated['month']);
        abort_unless(
            $month->toDateString() >= $registeredMonth
                && $month->toDateString() <= $currentMonth,
            404
        );

        if (! isset($validated['date'])) {
            return $this->dailyCalendar($student, $month);
        }

        abort_unless(
            str_starts_with($validated['date'], $validated['month']),
            404
        );

        if (! isset($validated['module_id'])) {
            return $this->dailyModules($student, $validated['date']);
        }

        $module = Module::forStudent($student)
            ->with(['subtopics' => fn ($query) => $query->select('id', 'module_id', 'title')->orderBy('title')])
            ->findOrFail($validated['module_id']);

        return $this->topicDetails($student, $module, $validated['date']);
    }

    public function topicQuizzes(Request $request, Subtopic $subtopic): JsonResponse
    {
        return $this->topicQuizzesForStudent($request->user(), $subtopic, true);
    }

    public function adminTopicQuizzes(Request $request, User $student, Subtopic $subtopic): JsonResponse
    {
        $this->ensureAdminCanViewStudent($request, $student);

        return $this->topicQuizzesForStudent($student, $subtopic, false);
    }

    private function topicQuizzesForStudent(User $student, Subtopic $subtopic, bool $includeResultUrl): JsonResponse
    {
        $subtopic = Subtopic::query()
            ->whereKey($subtopic->id)
            ->whereHas('module', fn ($query) => $query->forStudent($student))
            ->firstOrFail();
        $quizzes = Result::query()
            ->whereBelongsTo($student)
            ->whereHas('test', fn ($query) => $query
                ->where('department_id', $student->department_id)
                ->where('course_id', $student->course_id)
                ->where('subtopic_id', $subtopic->id)
                ->where('is_general', false))
            ->with('test:id,title')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'test_id', 'score', 'total_questions', 'created_at'])
            ->map(fn (Result $result): array => [
                'id' => $result->id,
                'title' => $result->test->title,
                'score' => $result->score,
                'total_questions' => $result->total_questions,
                'percentage' => $result->total_questions > 0
                    ? (int) round(($result->score / $result->total_questions) * 100)
                    : 0,
                'created_at' => $result->created_at->toIso8601String(),
                'url' => $includeResultUrl
                    ? route('student.results.show', $result)
                    : route('admin.students.results.show', ['student' => $student, 'result' => $result]),
            ]);

        return response()->json([
            'topic' => $subtopic->title,
            'quizzes' => $quizzes,
        ]);
    }

    private function ensureAdminCanViewStudent(Request $request, User $student): void
    {
        abort_unless(
            $request->user()->role === 'admin'
                && $student->role === 'student'
                && $request->user()->department_id === $student->department_id,
            404,
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subtopic_id' => ['required', 'integer', 'exists:subtopics,id'],
        ]);
        $student = $request->user();

        $subtopic = Subtopic::query()
            ->whereKey($validated['subtopic_id'])
            ->whereHas('module', fn ($query) => $query->forStudent($student))
            ->firstOrFail();
        $startedAt = now();

        $sessionData = DB::transaction(function () use ($student, $subtopic, $startedAt): array {
            User::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();

            $topicSeconds = $this->topicStudySeconds($student, $subtopic->id);
            StudySession::query()
                ->where('user_id', $student->id)
                ->whereNull('ended_at')
                ->update([
                    'ended_at' => $startedAt,
                    'pause_reason' => 'replaced',
                    'updated_at' => $startedAt,
                ]);

            $remainingSeconds = max(0, StudySession::TOPIC_STUDY_CAP_SECONDS - $topicSeconds);
            if ($remainingSeconds === 0) {
                return [
                    'session' => null,
                    'active_seconds' => $topicSeconds,
                    'remaining_seconds' => 0,
                    'capped' => true,
                ];
            }

            $session = StudySession::create([
                'user_id' => $student->id,
                'module_id' => $subtopic->module_id,
                'subtopic_id' => $subtopic->id,
                'activity_date' => $startedAt->toDateString(),
                'active_seconds' => 0,
                'started_at' => $startedAt,
                'last_heartbeat_at' => $startedAt,
            ]);

            return [
                'session' => $session,
                'active_seconds' => $topicSeconds,
                'remaining_seconds' => $remainingSeconds,
                'capped' => false,
            ];
        });

        return response()->json([
            'session_id' => $sessionData['session']?->id,
            'active_seconds' => $sessionData['active_seconds'],
            'remaining_seconds' => $sessionData['remaining_seconds'],
            'capped' => $sessionData['capped'],
            'verified' => $this->topicHasQuizAttempt($student, $subtopic->id),
        ], $sessionData['session'] === null ? 200 : 201);
    }

    public function heartbeat(Request $request, StudySession $studySession): JsonResponse
    {
        $student = $request->user();
        $this->ensureSessionBelongsToStudent($studySession, $student);
        $now = now();

        $heartbeat = DB::transaction(function () use ($studySession, $student, $now): array {
            User::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $session = $this->lockedSessionForStudent($studySession, $student);

            if ($session->ended_at !== null) {
                $topicSeconds = $this->topicStudySeconds($student, $session->subtopic_id);

                return [
                    'expired' => true,
                    'active_seconds' => $topicSeconds,
                    'remaining_seconds' => max(0, StudySession::TOPIC_STUDY_CAP_SECONDS - $topicSeconds),
                    'capped' => $topicSeconds >= StudySession::TOPIC_STUDY_CAP_SECONDS,
                ];
            }

            if ($session->activity_date->toDateString() !== $now->toDateString()) {
                $session->forceFill([
                    'ended_at' => $now,
                    'pause_reason' => 'midnight',
                ])->save();

                $topicSeconds = $this->topicStudySeconds($student, $session->subtopic_id);

                return [
                    'expired' => true,
                    'active_seconds' => $topicSeconds,
                    'remaining_seconds' => max(0, StudySession::TOPIC_STUDY_CAP_SECONDS - $topicSeconds),
                    'capped' => $topicSeconds >= StudySession::TOPIC_STUDY_CAP_SECONDS,
                ];
            }

            $topicSeconds = $this->topicStudySeconds($student, $session->subtopic_id);
            $remainingSeconds = max(0, StudySession::TOPIC_STUDY_CAP_SECONDS - $topicSeconds);
            $creditedSeconds = $this->recordElapsedTime($session, $now, $remainingSeconds);
            $topicSeconds += $creditedSeconds;
            $capped = $topicSeconds >= StudySession::TOPIC_STUDY_CAP_SECONDS;
            if ($capped) {
                $session->ended_at = $now;
                $session->pause_reason = 'topic_cap';
            }
            $session->save();

            return [
                'expired' => $capped,
                'active_seconds' => $topicSeconds,
                'remaining_seconds' => max(0, StudySession::TOPIC_STUDY_CAP_SECONDS - $topicSeconds),
                'capped' => $capped,
            ];
        });

        if ($heartbeat['expired']) {
            return response()->json([
                'message' => $heartbeat['capped']
                    ? 'This topic has reached its creditable study-time limit.'
                    : 'This reading session ended. Start a new session to continue tracking.',
                'active_seconds' => $heartbeat['active_seconds'],
                'remaining_seconds' => $heartbeat['remaining_seconds'],
                'capped' => $heartbeat['capped'],
                'verified' => $this->topicHasQuizAttempt($student, $studySession->subtopic_id),
            ], 409);
        }

        return response()->json([
            'active_seconds' => $heartbeat['active_seconds'],
            'remaining_seconds' => $heartbeat['remaining_seconds'],
            'capped' => $heartbeat['capped'],
            'verified' => $this->topicHasQuizAttempt($student, $studySession->subtopic_id),
        ]);
    }

    public function heartbeatCurrent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:study_sessions,id'],
        ]);

        return $this->heartbeat($request, StudySession::findOrFail($validated['session_id']));
    }

    public function stop(Request $request, StudySession $studySession): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'in:quiz,hidden,inactive,navigation,pagehide,manual'],
        ]);
        $student = $request->user();
        $this->ensureSessionBelongsToStudent($studySession, $student);
        $stoppedAt = now();

        $sessionData = DB::transaction(function () use ($studySession, $student, $stoppedAt, $validated): array {
            User::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $lockedSession = $this->lockedSessionForStudent($studySession, $student);
            $topicSeconds = $this->topicStudySeconds($student, $lockedSession->subtopic_id);

            if ($lockedSession->ended_at !== null) {
                return [
                    'session' => $lockedSession,
                    'active_seconds' => $topicSeconds,
                    'remaining_seconds' => max(0, StudySession::TOPIC_STUDY_CAP_SECONDS - $topicSeconds),
                    'capped' => $topicSeconds >= StudySession::TOPIC_STUDY_CAP_SECONDS,
                ];
            }

            if ($lockedSession->activity_date->toDateString() === $stoppedAt->toDateString()) {
                $remainingSeconds = max(0, StudySession::TOPIC_STUDY_CAP_SECONDS - $topicSeconds);
                $topicSeconds += $this->recordElapsedTime($lockedSession, $stoppedAt, $remainingSeconds);
            }

            $capped = $topicSeconds >= StudySession::TOPIC_STUDY_CAP_SECONDS;
            $lockedSession->forceFill([
                'ended_at' => $stoppedAt,
                'pause_reason' => $capped ? 'topic_cap' : $validated['reason'],
            ])->save();

            return [
                'session' => $lockedSession,
                'active_seconds' => $topicSeconds,
                'remaining_seconds' => max(0, StudySession::TOPIC_STUDY_CAP_SECONDS - $topicSeconds),
                'capped' => $capped,
            ];
        });

        return response()->json([
            'active_seconds' => $sessionData['active_seconds'],
            'remaining_seconds' => $sessionData['remaining_seconds'],
            'capped' => $sessionData['capped'],
            'stopped' => true,
            'verified' => $this->topicHasQuizAttempt($student, $studySession->subtopic_id),
        ]);
    }

    private function topicHasQuizAttempt(User $student, int $subtopicId): bool
    {
        return Result::query()
            ->where('user_id', $student->id)
            ->whereHas('test', fn ($query) => $query
                ->where('subtopic_id', $subtopicId)
                ->where('is_general', false))
            ->exists();
    }

    private function monthlyActivity(User $student, string $registeredMonth, string $currentMonth): JsonResponse
    {
        $months = [];
        $period = CarbonImmutable::parse($registeredMonth);
        $lastMonth = CarbonImmutable::parse($currentMonth);

        while ($period <= $lastMonth) {
            $months[$period->format('Y-m')] = [
                'month' => $period->format('Y-m'),
                'label' => $period->format('M Y'),
                'active_seconds' => 0,
            ];
            $period = $period->addMonth();
        }

        $totals = StudySession::query()
            ->where('user_id', $student->id)
            ->whereBetween('activity_date', [
                CarbonImmutable::parse($registeredMonth)->startOfDay(),
                now(),
            ])
            ->selectRaw('activity_date, SUM(active_seconds) as active_seconds')
            ->groupBy('activity_date')
            ->get();

        foreach ($totals as $dailyTotal) {
            $monthKey = CarbonImmutable::parse($dailyTotal->activity_date)->format('Y-m');

            if (isset($months[$monthKey])) {
                $months[$monthKey]['active_seconds'] += (int) $dailyTotal->active_seconds;
            }
        }

        return response()->json([
            'level' => 'months',
            'months' => array_values($months),
        ]);
    }

    private function dailyCalendar(User $student, CarbonImmutable $month): JsonResponse
    {
        $days = StudySession::query()
            ->where('user_id', $student->id)
            ->whereBetween('activity_date', [
                $month->startOfMonth()->startOfDay(),
                $month->endOfMonth()->endOfDay(),
            ])
            ->selectRaw('activity_date, SUM(active_seconds) as active_seconds')
            ->groupBy('activity_date')
            ->get()
            ->mapWithKeys(fn (StudySession $dailyTotal): array => [
                CarbonImmutable::parse($dailyTotal->activity_date)->toDateString() => (int) $dailyTotal->active_seconds,
            ]);

        return response()->json([
            'level' => 'days',
            'month' => $month->format('Y-m'),
            'label' => $month->format('F Y'),
            'first_weekday' => $month->startOfMonth()->dayOfWeek,
            'days_in_month' => $month->daysInMonth,
            'days' => collect(range(1, $month->daysInMonth))
                ->map(fn (int $day): array => [
                    'day' => $day,
                    'date' => $month->startOfMonth()->addDays($day - 1)->toDateString(),
                    'active_seconds' => (int) ($days[$month->startOfMonth()->addDays($day - 1)->toDateString()] ?? 0),
                ]),
        ]);
    }

    private function dailyModules(User $student, string $date): JsonResponse
    {
        $modules = Module::forStudent($student)
            ->orderBy('title')
            ->orderBy('id')
            ->get(['id', 'title']);
        $totals = StudySession::query()
            ->where('user_id', $student->id)
            ->whereBetween('activity_date', $this->dateRange($date))
            ->selectRaw('module_id, SUM(active_seconds) as active_seconds')
            ->groupBy('module_id')
            ->pluck('active_seconds', 'module_id');

        return response()->json([
            'level' => 'modules',
            'date' => $date,
            'modules' => $modules
                ->map(fn (Module $module): array => [
                    'id' => $module->id,
                    'title' => $module->title,
                    'active_seconds' => (int) ($totals[$module->id] ?? 0),
                ]),
        ]);
    }

    private function topicDetails(User $student, Module $module, string $date): JsonResponse
    {
        $subtopics = $module->subtopics;
        $subtopicIds = $subtopics->modelKeys();
        $sessions = StudySession::query()
            ->where('user_id', $student->id)
            ->whereBetween('activity_date', $this->dateRange($date))
            ->whereIn('subtopic_id', $subtopicIds)
            ->get(['subtopic_id', 'active_seconds', 'pause_reason']);
        $studyTime = $sessions->groupBy('subtopic_id');
        $resultsBySubtopic = Result::query()
            ->whereBelongsTo($student)
            ->whereDate('created_at', $date)
            ->whereHas('test', fn ($query) => $query
                ->where('department_id', $student->department_id)
                ->where('course_id', $student->course_id)
                ->whereIn('subtopic_id', $subtopicIds))
            ->with('test:id,subtopic_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'test_id', 'score', 'total_questions'])
            ->groupBy(fn (Result $result): int => $result->test->subtopic_id);

        return response()->json([
            'level' => 'topics',
            'date' => $date,
            'module' => ['id' => $module->id, 'title' => $module->title],
            'topics' => $subtopics->map(function (Subtopic $subtopic) use ($studyTime, $resultsBySubtopic): array {
                $topicSessions = $studyTime->get($subtopic->id, collect());
                $latestResult = $resultsBySubtopic->get($subtopic->id)?->first();
                $passed = $latestResult !== null
                    && $latestResult->total_questions > 0
                    && ($latestResult->score / $latestResult->total_questions) >= 0.85;

                return [
                    'id' => $subtopic->id,
                    'title' => $subtopic->title,
                    'active_seconds' => (int) $topicSessions->sum('active_seconds'),
                    'quiz_status' => $latestResult === null
                        ? ($topicSessions->isNotEmpty() ? 'In Progress' : 'Not Started')
                        : ($passed ? 'Passed' : 'Needs Review'),
                    'quiz_auto_paused' => $topicSessions->contains('pause_reason', 'quiz'),
                    'quiz_score' => $latestResult?->total_questions > 0
                        ? (int) round(($latestResult->score / $latestResult->total_questions) * 100)
                        : null,
                ];
            }),
        ]);
    }

    private function ensureSessionBelongsToStudent(StudySession $studySession, User $student): void
    {
        abort_unless(
            $studySession->user_id === $student->id
                && Module::forStudent($student)->whereKey($studySession->module_id)->exists(),
            404
        );
    }

    private function lockedSessionForStudent(StudySession $studySession, User $student): StudySession
    {
        return StudySession::query()
            ->where('user_id', $student->id)
            ->whereKey($studySession->id)
            ->whereHas('module', fn ($query) => $query->forStudent($student))
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function recordElapsedTime(
        StudySession $session,
        CarbonInterface $now,
        int $remainingSeconds,
    ): int {
        $elapsedSeconds = max(0, $now->getTimestamp() - $session->last_heartbeat_at->getTimestamp());
        if ($elapsedSeconds < StudySession::MIN_HEARTBEAT_SECONDS) {
            return 0;
        }

        $creditedSeconds = min(
            $elapsedSeconds,
            StudySession::MAX_HEARTBEAT_SECONDS,
            $remainingSeconds,
        );
        $session->active_seconds += $creditedSeconds;
        $session->last_heartbeat_at = $now;

        return $creditedSeconds;
    }

    private function topicStudySeconds(User $student, int $subtopicId): int
    {
        return (int) StudySession::query()
            ->where('user_id', $student->id)
            ->where('subtopic_id', $subtopicId)
            ->sum('active_seconds');
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function dateRange(string $date): array
    {
        $day = CarbonImmutable::parse($date);

        return [$day->startOfDay(), $day->endOfDay()];
    }
}
