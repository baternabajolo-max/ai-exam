<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Module;
use App\Models\Question;
use App\Models\Result;
use App\Models\Test;
use App\Services\AchievementUnlockService;
use App\Services\AiService;
use App\Services\LeaderboardPointsService;
use App\Services\StudentReadinessService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentController extends Controller
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function dashboard(
        Request $request,
        StudentReadinessService $readinessService,
        LeaderboardPointsService $leaderboardPointsService,
    ): View {
        $student = auth()->user()->load(['department', 'course']);
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
        $leaderboardPointsService->recalculateFor($student);
        $modules = Module::forStudent($student)
            ->with('subtopics')
            ->orderBy('title')
            ->get();
        $subtopics = $modules->flatMap->subtopics;
        $subtopicTitles = $subtopics->pluck('title');
        $subtopicTests = Test::where('department_id', $student->department_id)
            ->where(function ($query) use ($subtopicTitles) {
                $query->whereIn('topic', $subtopicTitles)
                    ->orWhereIn('title', $subtopicTitles->map(fn (string $title): string => $title.' - Review Quiz'));
            })
            ->withCount('questions')
            ->get();
        $latestScores = Result::where('user_id', $student->id)
            ->whereIn('test_id', $subtopicTests->pluck('id'))
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->groupBy('test_id')
            ->map(fn ($results): int => (int) $results->first()->score);
        $completedSubtopics = $subtopics->filter(function ($subtopic) use ($subtopicTests, $latestScores): bool {
            $tests = $subtopicTests->filter(fn (Test $test): bool => $test->topic === $subtopic->title || $test->title === $subtopic->title.' - Review Quiz');
            $latestPoints = $tests->max(function (Test $test) use ($latestScores): int {
                $questions = max(0, (int) $test->questions_count);
                $score = (int) ($latestScores[$test->id] ?? 0);

                return $questions > 0 ? (int) round(($score / $questions) * 30) : 0;
            });

            return $latestPoints >= 30;
        })->count();

        $testHistory = Result::query()
            ->whereBelongsTo($student)
            ->whereHas('test', fn ($query) => $query->where('is_general', true))
            ->with('test')
            ->latest()
            ->take(10)
            ->get()
            ->each(function (Result $result): void {
                $result->display_total_questions = $this->resultTotalQuestions($result);
                $result->display_percentage = $this->resultPercentage($result);
            });
        [
            $readinessModules,
            $readinessScore,
            $readinessAttemptsCount,
            $readinessAssessedModuleCount,
            $readinessAssessedQuestionCount,
        ] = $readinessService->forStudent($student, $modules);
        $periodResults = Result::query()
            ->where('user_id', $student->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereHas('test', fn ($query) => $query
                ->where('is_general', true)
                ->where('title', 'not like', '% - Review Quiz'))
            ->with('test:id,title')
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
        $weakestModule = collect($readinessModules)
            ->filter(fn (array $module): bool => $module['score'] !== null)
            ->sortBy('score')
            ->first();
        $weakestModuleRecord = $weakestModule === null
            ? null
            : $modules->first(fn (Module $module): bool => $module->title === $weakestModule['module']);

        return view('student.dashboard', compact(
            'student',
            'modules',
            'completedSubtopics',
            'testHistory',
            'readinessModules',
            'readinessScore',
            'readinessAttemptsCount',
            'readinessAssessedModuleCount',
            'readinessAssessedQuestionCount',
            'analyticsDays',
            'periodStartDate',
            'periodEndDate',
            'periodStart',
            'periodEnd',
            'studentAnalytics',
            'studentPerformance',
            'weakestModule',
            'weakestModuleRecord',
        ));
    }

    public function takeTest(Test $test)
    {
        if ($test->questions()->count() === 0) {
            try {
                $generatedQuestions = $this->aiService->generateTestQuestions(
                    $test->topic,
                    5,
                    $test->difficulty ?? 'medium'
                );

                foreach ($generatedQuestions as $q) {
                    Question::create([
                        'test_id' => $test->id,
                        'question' => $q['question'],
                        'options' => json_encode($q['options']),
                        'correct_answer' => $q['correct_answer'],
                        'explanation' => $q['explanation'] ?? null,
                    ]);
                }
            } catch (\Exception $e) {
                return back()->withErrors(['error' => 'Failed to generate AI questions: '.$e->getMessage()]);
            }
        }

        $test->load('questions');

        return view('student.take-test', compact('test'));
    }

    public function submitTest(Request $request, Test $test)
    {
        $answers = $request->input('answers', []);
        $score = 0;
        $totalQuestions = $test->questions()->count();
        $answersSummary = [];

        foreach ($test->questions as $question) {
            $studentAnswer = $answers[$question->id] ?? null;
            $isCorrect = ($studentAnswer == $question->correct_answer);
            if ($isCorrect) {
                $score++;
            }
            $answersSummary[$question->id] = [
                'question' => $question->question,
                'student_answer' => $studentAnswer,
                'correct_answer' => $question->correct_answer,
                'is_correct' => $isCorrect,
                'explanation' => $question->explanation,
            ];
        }

        // Determine if student passed (e.g., 50% or higher)
        $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;
        $passed = $percentage >= 50;

        Result::create([
            'user_id' => auth()->id(),
            'test_id' => $test->id,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'passed' => $passed,
            'answers_summary' => $answersSummary,
        ]);

        return redirect()->route('student.results.index')->with('success', "Test submitted successfully! Your score: {$score}/{$totalQuestions}");
    }

    public function profile()
    {
        $student = auth()->user()->load([
            'department',
            'course',
            'achievements' => fn ($query) => $query->wherePivotNotNull('claimed_at'),
            'receivedNotifications.admin',
        ]);
        $claimedAchievements = $student->achievements;

        return view('student.profile.index', [
            'student' => $student,
            'claimedAchievements' => $claimedAchievements,
        ]);
    }

    public function results()
    {
        $results = Result::where('user_id', auth()->id())->with('test')->latest()->get();

        return view('student.results.index', compact('results'));
    }

    public function reviewers()
    {
        return view('student.reviewers.index');
    }

    public function showResult(Result $result)
    {
        if ($result->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to test result.');
        }

        return view('student.results.show', compact('result'));
    }

    public function achievements(
        AchievementUnlockService $achievementUnlockService,
        LeaderboardPointsService $leaderboardPointsService,
    ) {
        $user = auth()->user();
        $leaderboardPointsService->recalculateFor($user);
        $user->refresh();
        $achievementUnlockService->syncEligible($user);
        $userAchievements = DB::table('user_achievements')
            ->where('user_id', $user->id)
            ->pluck('achievement_id');
        $claimedAchievementIds = DB::table('user_achievements')
            ->where('user_id', $user->id)
            ->whereNotNull('claimed_at')
            ->pluck('achievement_id');
        $achievements = Achievement::where('is_active', true)->orderBy('required_points', 'asc')->get();
        $unlockedIds = $userAchievements->map(fn ($id): int => (int) $id)->all();
        $claimedIds = $claimedAchievementIds->map(fn ($id): int => (int) $id)->all();

        // Points to: resources/views/student/Achievements/index.blade.php
        return view('student.achievements.index', compact('achievements', 'unlockedIds', 'claimedIds', 'user'));
    }

    public function claimAchievement(Achievement $achievement, AchievementUnlockService $achievementUnlockService): RedirectResponse
    {
        $student = auth()->user();
        $unlockedAchievement = $student->achievements()
            ->whereKey($achievement->id)
            ->firstOrFail();

        abort_unless($achievement->is_active, 403);
        abort_unless($achievementUnlockService->isEligible($student, $achievement), 403);

        if ($unlockedAchievement->pivot->claimed_at !== null) {
            return back()->with('success', 'You already claimed this achievement.');
        }

        $student->achievements()->updateExistingPivot($achievement->id, ['claimed_at' => now()]);
        $student->update(['badge_icon' => $achievement->icon]);

        return back()->with('success', 'Achievement claimed and added to your profile.');
    }

    private function resultPercentage(Result $result): int
    {
        $totalQuestions = $this->resultTotalQuestions($result);

        return $totalQuestions > 0 ? (int) round(($result->score / $totalQuestions) * 100) : 0;
    }

    private function resultTotalQuestions(Result $result): int
    {
        if ($result->total_questions > 0) {
            return (int) $result->total_questions;
        }

        $answers = $result->answers;
        while (is_string($answers)) {
            $decoded = json_decode($answers, true);
            if (! is_string($decoded)) {
                $answers = $decoded;
                break;
            }
            $answers = $decoded;
        }

        if (is_array($answers) && count($answers) > 0) {
            return count($answers);
        }

        return $result->relationLoaded('test')
            ? $result->test->questions()->count()
            : 0;
    }
}
