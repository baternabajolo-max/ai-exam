<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Test as StudentTest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StudentStreakTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_finishing_quizzes_and_general_tests_adds_at_most_one_streak_per_day(): void
    {
        $this->freezeTime();

        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $quiz = $this->createTestWithQuestion($department, false);
        $generalTest = $this->createTestWithQuestion($department, true);

        $this->submitCorrectAnswer($student, $quiz)
            ->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'daily_streak' => 1,
        ]);
        $this->assertSame(today()->toDateString(), $student->fresh()->daily_streak_last_completed_at->toDateString());

        $this->submitCorrectAnswer($student, $quiz)
            ->assertRedirect();
        $this->submitCorrectAnswer($student, $generalTest)
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'daily_streak' => 1,
        ]);
        $this->assertSame(today()->toDateString(), $student->fresh()->daily_streak_last_completed_at->toDateString());

        $this->travelTo(now()->addDay());

        $this->submitCorrectAnswer($student, $generalTest)
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'daily_streak' => 2,
        ]);
        $this->assertSame(today()->toDateString(), $student->fresh()->daily_streak_last_completed_at->toDateString());
    }

    public function test_streak_indicator_freezes_after_one_missed_day_and_shows_zero_after_two(): void
    {
        $this->freezeTime();

        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'daily_streak' => 4,
            'daily_streak_last_completed_at' => today()->subDays(2)->toDateString(),
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertSee('student-streak-indicator frozen', false)
            ->assertSee('bi-snow')
            ->assertSee('4');

        $student->update(['daily_streak_last_completed_at' => today()->subDays(3)->toDateString()]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertSee('student-streak-indicator inactive', false)
            ->assertSee('bi-fire')
            ->assertSee('>0<', false);
    }

    public function test_completing_again_after_one_missed_day_thaws_the_existing_streak(): void
    {
        $this->freezeTime();

        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'daily_streak' => 6,
            'daily_streak_last_completed_at' => today()->subDays(2)->toDateString(),
        ]);

        $student->recordQuizCompletionStreak();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'daily_streak' => 6,
        ]);
        $this->assertSame(today()->toDateString(), $student->fresh()->daily_streak_last_completed_at->toDateString());

        $this->travelTo(now()->addDay());
        $student->recordQuizCompletionStreak();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'daily_streak' => 7,
        ]);
        $this->assertSame(today()->toDateString(), $student->fresh()->daily_streak_last_completed_at->toDateString());
    }

    private function createTestWithQuestion(Department $department, bool $isGeneral): StudentTest
    {
        $test = StudentTest::create([
            'title' => $isGeneral ? 'General Test' : 'Topic Quiz',
            'department_id' => $department->id,
            'is_general' => $isGeneral,
            'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
        ]);
        $test->questions()->create([
            'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
            'question' => 'Which option is correct?',
            'options' => $isGeneral ? ['Option A', 'Option B', 'Option C', 'Correct'] : ['Correct', 'Incorrect'],
            'correct_answer' => 'Correct',
        ]);

        return $test;
    }

    private function submitCorrectAnswer(User $student, StudentTest $test): TestResponse
    {
        $question = $test->questions()->firstOrFail();

        return $this->actingAs($student)->post(route('student.tests.submit', $test), [
            'answers' => [$question->id => 'Correct'],
            'question_order' => [$question->id],
        ]);
    }
}
