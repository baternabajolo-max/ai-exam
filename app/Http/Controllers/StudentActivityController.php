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
    private const MAX_HEARTBEAT_CREDIT_SECONDS = 45;

    public function index(Request $request): JsonResponse
    {
        return $this->activityForStudent($request, $request->user());
    }

    public function adminIndex(Request $request, User $student): JsonResponse
    {
        $this->ensureAdminCanViewStudent($request, $student);

        return $this->activityForStudent($request, $student);
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

        $studySession = DB::transaction(function () use ($student, $subtopic, $startedAt): StudySession {
            User::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();

            StudySession::query()
                ->where('user_id', $student->id)
                ->whereNull('ended_at')
                ->update([
                    'ended_at' => $startedAt,
                    'pause_reason' => 'replaced',
                    'updated_at' => $startedAt,
                ]);

            return StudySession::create([
                'user_id' => $student->id,
                'module_id' => $subtopic->module_id,
                'subtopic_id' => $subtopic->id,
                'activity_date' => $startedAt->toDateString(),
                'active_seconds' => 0,
                'started_at' => $startedAt,
                'last_heartbeat_at' => $startedAt,
            ]);
        });

        return response()->json([
            'session_id' => $studySession->id,
            'active_seconds' => $studySession->active_seconds,
        ], 201);
    }

    public function heartbeat(Request $request, StudySession $studySession): JsonResponse
    {
        $student = $request->user();
        $this->ensureSessionBelongsToStudent($studySession, $student);
        $now = now();

        $heartbeat = DB::transaction(function () use ($studySession, $student, $now): array {
            $session = $this->lockedSessionForStudent($studySession, $student);

            if ($session->ended_at !== null) {
                return ['expired' => true, 'active_seconds' => $session->active_seconds];
            }

            if ($session->activity_date->toDateString() !== $now->toDateString()) {
                $session->forceFill([
                    'ended_at' => $now,
                    'pause_reason' => 'midnight',
                ])->save();

                return ['expired' => true, 'active_seconds' => $session->active_seconds];
            }

            $this->recordElapsedTime($session, $now);
            $session->save();

            return ['expired' => false, 'active_seconds' => $session->active_seconds];
        });

        if ($heartbeat['expired']) {
            return response()->json([
                'message' => 'This reading session ended. Start a new session to continue tracking.',
                'active_seconds' => $heartbeat['active_seconds'],
            ], 409);
        }

        return response()->json([
            'active_seconds' => $heartbeat['active_seconds'],
        ]);
    }

    public function stop(Request $request, StudySession $studySession): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'in:quiz,hidden,navigation,pagehide,manual'],
        ]);
        $student = $request->user();
        $this->ensureSessionBelongsToStudent($studySession, $student);
        $stoppedAt = now();

        $session = DB::transaction(function () use ($studySession, $student, $stoppedAt, $validated): StudySession {
            $lockedSession = $this->lockedSessionForStudent($studySession, $student);

            if ($lockedSession->ended_at !== null) {
                return $lockedSession;
            }

            if ($lockedSession->activity_date->toDateString() === $stoppedAt->toDateString()) {
                $this->recordElapsedTime($lockedSession, $stoppedAt);
            }

            $lockedSession->forceFill([
                'ended_at' => $stoppedAt,
                'pause_reason' => $validated['reason'],
            ])->save();

            return $lockedSession;
        });

        return response()->json([
            'active_seconds' => $session->active_seconds,
            'stopped' => true,
        ]);
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

    private function recordElapsedTime(StudySession $session, CarbonInterface $now): void
    {
        $elapsedSeconds = max(0, $now->getTimestamp() - $session->last_heartbeat_at->getTimestamp());
        $session->active_seconds += min($elapsedSeconds, self::MAX_HEARTBEAT_CREDIT_SECONDS);
        $session->last_heartbeat_at = $now;
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
