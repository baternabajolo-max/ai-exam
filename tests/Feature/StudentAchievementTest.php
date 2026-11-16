<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Department;
use App\Models\Module;
use App\Models\Question;
use App\Models\Result;
use App\Models\StudentNotification;
use App\Models\Subtopic;
use App\Models\Test as StudentTest;
use App\Models\User;
use App\Services\AchievementUnlockService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentAchievementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_activity_based_perfect_quiz_badge_is_unlocked_by_a_perfect_attempt(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student->update(['department_id' => $department->id]);
        $test = StudentTest::create([
            'title' => 'Perfect Quiz',
            'topic' => 'Science',
            'department_id' => $department->id,
            'is_general' => false,
        ]);
        $achievement = Achievement::create([
            'title' => 'Perfect Run',
            'description' => 'Score 100% in a quiz attempt.',
            'icon' => 'bi-award-fill',
            'required_points' => 0,
            'requirements' => ['type' => 'perfect_quiz', 'required' => 1],
            'requirements_text' => 'Score 100% in a quiz attempt.',
            'is_active' => true,
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 1,
            'total_questions' => 1,
            'answers' => [],
        ]);

        app(AchievementUnlockService::class)->syncEligible($student);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $student->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_unclaimed_achievement_is_locked_again_when_points_drop_below_requirement(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'total_points' => 3,
        ]);
        $achievement = Achievement::create([
            'title' => 'Quiz Master',
            'description' => 'Earn 100 total points.',
            'icon' => 'bi-trophy-fill',
            'required_points' => 100,
            'is_active' => true,
        ]);
        $student->achievements()->attach($achievement->id, ['claimed_at' => null]);

        app(AchievementUnlockService::class)->syncEligible($student);

        $this->assertDatabaseMissing('user_achievements', [
            'user_id' => $student->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    public function test_latest_quiz_attempt_replaces_points_instead_of_keeping_the_best_score(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'total_points' => 0,
        ]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Photosynthesis']);
        $test = StudentTest::create([
            'title' => 'Photosynthesis - Review Quiz',
            'topic' => $subtopic->title,
            'department_id' => $department->id,
            'is_general' => false,
            'subtopic_id' => $subtopic->id,
        ]);
        $questions = collect(range(1, 10))->map(fn (int $number) => Question::create([
            'test_id' => $test->id,
            'question' => 'Question '.$number,
            'options' => [],
            'correct_answer' => 'A',
        ]));

        $this->actingAs($student)->post(route('student.tests.submit', $test), [
            'answers' => $questions->mapWithKeys(fn (Question $question) => [$question->id => 'A'])->all(),
            'question_order' => $questions->pluck('id')->all(),
        ]);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'total_points' => 30]);

        $lowAnswers = $questions->mapWithKeys(fn (Question $question, int $index) => [
            $question->id => $index === 0 ? 'A' : 'B',
        ])->all();
        $this->actingAs($student)->post(route('student.tests.submit', $test), [
            'answers' => $lowAnswers,
            'question_order' => $questions->pluck('id')->all(),
        ]);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'total_points' => 3]);
    }

    public function test_unlocked_achievement_is_not_claimed_until_the_student_claims_it(): void
    {
        $this->freezeTime();

        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Photosynthesis']);
        $test = StudentTest::create([
            'title' => 'Photosynthesis - Review Quiz',
            'topic' => $subtopic->title,
            'department_id' => $department->id,
            'is_general' => false,
            'subtopic_id' => $subtopic->id,
        ]);
        $achievement = Achievement::create([
            'title' => 'First Perfect Score',
            'description' => 'Earn full points on an assessment.',
            'icon' => 'bi-star-fill',
            'required_points' => 30,
            'is_active' => true,
        ]);
        $question = Question::create([
            'test_id' => $test->id,
            'question' => 'Plants use light to produce ____.',
            'options' => [],
            'correct_answer' => 'GLUCOSE',
            'explanation' => 'Glucose stores chemical energy.',
        ]);

        $submission = $this->actingAs($student)
            ->post(route('student.tests.submit', $test), [
                'answers' => [$question->id => 'glucose'],
                'question_order' => [$question->id],
            ]);
        $result = Result::where('user_id', $student->id)->firstOrFail();
        $submission->assertRedirect(route('student.results.show', $result));

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $student->id,
            'achievement_id' => $achievement->id,
            'claimed_at' => null,
        ]);
        $this->assertDatabaseCount('student_notifications', 1);
        $this->assertDatabaseHas('student_notifications', [
            'student_id' => $student->id,
            'subject' => 'Achievement unlocked: First Perfect Score',
        ]);

        $notificationsBeforeClaim = StudentNotification::count();
        $this->actingAs($student)
            ->post(route('student.achievements.claim', $achievement))
            ->assertSessionHas('success', 'Achievement claimed and added to your profile.');

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $student->id,
            'achievement_id' => $achievement->id,
            'claimed_at' => now()->toDateTimeString(),
        ]);
        $this->assertNotNull($student->fresh()->badge_icon);
        $this->assertSame($notificationsBeforeClaim, StudentNotification::count());
        $this->assertDatabaseMissing('user_achievements', [
            'user_id' => $student->id,
            'achievement_id' => $achievement->id,
            'claimed_at' => null,
        ]);

        $profileResponse = $this->actingAs($student)
            ->get(route('student.profile'))
            ->assertSee('Unlocked Achievements')
            ->assertSee('First Perfect Score')
            ->assertSee('Earn full points on an assessment.');

        $achievementPage = $this->actingAs($student)
            ->get(route('student.achievements'))
            ->assertViewHas('user', fn (User $profileUser): bool => $profileUser->id === $student->id)
            ->assertViewHas('unlockedIds', [$achievement->id])
            ->assertViewHas('claimedIds', [$achievement->id]);
        $achievementPage
            ->assertSee('Claimed')
            ->assertSee('badge-claimed');
        $this->assertDatabaseCount('user_achievements', 1);
        $this->assertDatabaseCount('student_notifications', 1);

        $this->actingAs($student)
            ->get(route('student.notifications.index'))
            ->assertSee('Go to achievements')
            ->assertSee(route('student.achievements'), false)
            ->assertSee('data-notification-sender="'.config('app.name').'"', false);
    }
}
