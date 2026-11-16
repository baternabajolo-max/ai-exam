<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Module;
use App\Models\Result;
use App\Models\StudentNotification;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AchievementUnlockService
{
    /**
     * @return Collection<int, Achievement>
     */
    public function syncEligible(User $student): Collection
    {
        $eligibleAchievementIds = Achievement::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (Achievement $achievement): bool => $this->isEligible($student, $achievement))
            ->pluck('id');

        DB::table('user_achievements')
            ->where('user_id', $student->id)
            ->whereNull('claimed_at')
            ->whereNotIn('achievement_id', $eligibleAchievementIds)
            ->delete();

        $alreadyUnlockedIds = DB::table('user_achievements')
            ->where('user_id', $student->id)
            ->pluck('achievement_id');
        $newlyUnlocked = Achievement::query()
            ->where('is_active', true)
            ->whereNotIn('id', $alreadyUnlockedIds)
            ->get();
        $newlyUnlocked = $newlyUnlocked->filter(fn (Achievement $achievement): bool => $this->isEligible($student, $achievement));

        foreach ($newlyUnlocked as $achievement) {
            $student->achievements()->attach($achievement->id, ['claimed_at' => null]);
            StudentNotification::create([
                'student_id' => $student->id,
                'admin_id' => null,
                'subject' => 'Achievement unlocked: '.$achievement->title,
                'message' => 'You unlocked '.$achievement->title.'. Claim it from your Achievements page to display it on your profile.',
            ]);
        }

        return $newlyUnlocked;
    }

    public function isEligible(User $student, Achievement $achievement): bool
    {
        $requirements = $achievement->requirements;
        if (! is_array($requirements) || $requirements === []) {
            return (int) $achievement->required_points <= (int) $student->total_points;
        }

        $required = max(1, (int) ($requirements['required'] ?? $requirements['minimum'] ?? 1));

        return match ($requirements['type'] ?? null) {
            'total_points' => $student->total_points >= $required,
            'mistake_analysis' => $this->resultsWithMistakes($student) >= $required,
            'failed_then_perfect_retake' => $this->hasFailedThenPerfectRetake($student),
            'lesson_completion' => StudySession::where('user_id', $student->id)
                ->whereNotNull('subtopic_id')
                ->where('active_seconds', '>=', 60)
                ->distinct()
                ->count('subtopic_id') >= $required,
            'perfect_quiz' => Result::where('user_id', $student->id)
                ->get(['score', 'total_questions'])
                ->filter(fn (Result $result): bool => $result->total_questions > 0
                    && $result->score >= $result->total_questions)
                ->count() >= $required,
            'module_mastery' => $this->masteredModuleCount($student) >= $required,
            'personal_record' => $this->personalRecordCount($student) >= $required,
            'daily_streak' => $student->daily_streak_state === 'active' && $student->visible_daily_streak >= $required,
            'leaderboard_rank' => ($rank = $this->leaderboardRank($student)) !== null && $rank <= $required,
            default => false,
        };
    }

    private function resultsWithMistakes(User $student): int
    {
        return Result::where('user_id', $student->id)
            ->get(['answers'])
            ->filter(function (Result $result): bool {
                $answers = is_array($result->answers) ? $result->answers : [];

                return collect($answers)->contains(function (mixed $answer): bool {
                    return is_array($answer) && ($answer['is_correct'] ?? true) === false;
                });
            })
            ->count();
    }

    private function hasFailedThenPerfectRetake(User $student): bool
    {
        return Result::where('user_id', $student->id)
            ->where('total_questions', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'test_id', 'score', 'total_questions', 'passed', 'created_at'])
            ->groupBy('test_id')
            ->contains(function (Collection $attempts): bool {
                $failedSeen = false;

                foreach ($attempts as $attempt) {
                    $failed = ! (bool) $attempt->passed
                        || $attempt->score / $attempt->total_questions < 1;

                    if ($failed) {
                        $failedSeen = true;
                    } elseif ($failedSeen && $attempt->score >= $attempt->total_questions) {
                        return true;
                    }
                }

                return false;
            });
    }

    private function masteredModuleCount(User $student): int
    {
        return Module::where('department_id', $student->department_id)
            ->with('subtopics')
            ->get()
            ->filter(function (Module $module) use ($student): bool {
                if ($module->subtopics->isEmpty()) {
                    return false;
                }

                return $module->subtopics->every(
                    fn ($subtopic): bool => $subtopic->earnedPointsForUser($student->id) >= 30
                );
            })
            ->count();
    }

    private function personalRecordCount(User $student): int
    {
        $recordCount = 0;

        Result::where('user_id', $student->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'user_id', 'test_id', 'score', 'total_questions', 'created_at'])
            ->groupBy('test_id')
            ->each(function (Collection $results) use (&$recordCount): void {
                $highestPercentage = -1.0;
                $improved = false;

                foreach ($results as $result) {
                    $percentage = $result->total_questions > 0
                        ? $result->score / $result->total_questions
                        : 0;

                    if ($percentage > $highestPercentage) {
                        $highestPercentage = $percentage;
                        if ($result !== $results->first()) {
                            $improved = true;
                        }
                    }
                }

                if ($improved) {
                    $recordCount++;
                }
            });

        return $recordCount;
    }

    private function leaderboardRank(User $student): ?int
    {
        $leaders = User::where('role', 'student')
            ->where('department_id', $student->department_id)
            ->orderByDesc('total_points')
            ->orderBy('name')
            ->pluck('id');

        $rank = $leaders->search($student->id);

        return $rank === false ? null : $rank + 1;
    }
}
