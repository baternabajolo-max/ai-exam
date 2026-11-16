<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BadgeAchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            DB::table('user_achievements')->delete();
            Achievement::query()->delete();

            $badges = [
                [
                    'title' => 'Error Analyst',
                    'description' => 'Dedication to continuous improvement. Earn a 100% score on a quiz retake after initially failing it.',
                    'badge_image' => 'badges/Erroe Analyst.png',
                ],
                [
                    'title' => 'Lesson Master',
                    'description' => 'Comprehensive knowledge acquired. Master 3 complete modules.',
                    'badge_image' => 'badges/Lesson Master.png',
                ],
                [
                    'title' => 'Perfect Run',
                    'description' => 'Flawless execution. Earn a perfect score on 3 separate quiz attempts.',
                    'badge_image' => 'badges/Perfect Run.png',
                ],
                [
                    'title' => 'Record Breaker',
                    'description' => 'Maintain an active daily study streak for 30 consecutive days.',
                    'badge_image' => 'badges/Record Breaker.png',
                ],
                [
                    'title' => 'Top Rank',
                    'description' => 'Secure a spot in the Top 3 of your department leaderboard.',
                    'badge_image' => 'badges/Top Rank.png',
                ],
            ];

            foreach ($badges as $badge) {
                Achievement::create([
                    ...$badge,
                    'icon' => 'bi-award-fill',
                    'requirements_text' => $badge['description'],
                    'requirements' => match ($badge['title']) {
                        'Error Analyst' => ['type' => 'failed_then_perfect_retake', 'required' => 1],
                        'Lesson Master' => ['type' => 'module_mastery', 'required' => 3],
                        'Perfect Run' => ['type' => 'perfect_quiz', 'required' => 3],
                        'Record Breaker' => ['type' => 'daily_streak', 'required' => 30],
                        'Top Rank' => ['type' => 'leaderboard_rank', 'required' => 3],
                    },
                    'required_points' => 0,
                    'is_active' => true,
                ]);
            }
        });
    }
}
