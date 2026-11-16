<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Course;
use App\Models\Department;
use App\Models\Module;
use App\Models\Question;
use App\Models\Result;
use App\Models\Subtopic;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentFlashcardFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_can_save_a_flashcard_with_a_supported_color(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $module = Module::create(['department_id' => $department->id, 'title' => 'Networking']);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'TCP']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);

        $this->actingAs($student)
            ->post(route('student.flashcards.store'), [
                'front' => 'TCP',
                'back' => 'Transmission Control Protocol',
                'color' => 'pink',
                'subtopic_id' => $subtopic->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('flashcards', [
            'user_id' => $student->id,
            'front' => 'TCP',
            'color' => 'pink',
        ]);
    }

    public function test_student_can_edit_an_owned_flashcard(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $flashcard = $student->flashcards()->create([
            'front' => 'TCP',
            'back' => null,
            'color' => 'blue',
        ]);

        $this->actingAs($student)
            ->put(route('student.flashcards.update', $flashcard), [
                'front' => 'TCP',
                'back' => 'Transmission Control Protocol',
                'description' => 'Updated definition',
                'color' => 'green',
            ])
            ->assertSessionHas('success', 'Flashcard updated.');

        $this->assertDatabaseHas('flashcards', [
            'id' => $flashcard->id,
            'back' => 'Transmission Control Protocol',
            'description' => 'Updated definition',
            'color' => 'green',
        ]);
    }

    public function test_flashcard_deletion_uses_the_shared_sweetalert_confirmation(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $student->flashcards()->create([
            'front' => 'TCP',
            'back' => 'Transmission Control Protocol',
            'color' => 'blue',
        ]);

        $this->actingAs($student)
            ->get(route('student.flashcards.index'))
            ->assertSee('data-confirm-title="Delete this flashcard?"', false)
            ->assertSee('data-confirm-button="Delete flashcard"', false)
            ->assertSee('js/confirm-actions.js')
            ->assertDontSee('onsubmit="return confirm(', false);
    }

    public function test_removing_a_subtopic_removes_points_earned_from_its_quiz(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $course = Course::create(['name' => 'Computer Science', 'code' => 'CS', 'department_id' => $department->id]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'total_points' => 24,
        ]);
        $module = Module::create(['department_id' => $department->id, 'course_id' => $course->id, 'title' => 'Networking']);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'TCP']);
        $test = Test::create([
            'department_id' => $department->id,
            'course_id' => $course->id,
            'title' => 'TCP - Review Quiz',
            'topic' => 'TCP',
        ]);

        foreach (range(1, 10) as $number) {
            Question::create([
                'test_id' => $test->id,
                'question' => 'Question '.$number,
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => 'A',
            ]);
        }

        Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 8,
            'total_questions' => 10,
            'answers' => array_fill(0, 10, ['is_correct' => true]),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.subtopics.destroy', $subtopic))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $student->id, 'total_points' => 0]);
        $this->assertDatabaseMissing('subtopics', ['id' => $subtopic->id]);
        $this->assertDatabaseMissing('tests', ['id' => $test->id]);
    }

    public function test_removing_a_subtopic_recalculates_remaining_points_and_revokes_ineligible_achievements(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $course = Course::create(['name' => 'Computer Science', 'code' => 'CS', 'department_id' => $department->id]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'total_points' => 18,
        ]);
        $achievement = Achievement::create([
            'title' => 'Ten Points',
            'description' => 'Earn ten points.',
            'required_points' => 10,
            'is_active' => true,
        ]);
        $student->achievements()->attach($achievement);
        $module = Module::create(['department_id' => $department->id, 'course_id' => $course->id, 'title' => 'Networking']);
        $removedSubtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'TCP']);
        Subtopic::create(['module_id' => $module->id, 'title' => 'UDP']);
        $removedTest = Test::create([
            'department_id' => $department->id,
            'course_id' => $course->id,
            'title' => 'TCP - Review Quiz',
            'topic' => 'TCP',
        ]);
        $remainingTest = Test::create([
            'department_id' => $department->id,
            'course_id' => $course->id,
            'title' => 'UDP - Review Quiz',
            'topic' => 'UDP',
        ]);

        foreach ([$removedTest, $remainingTest] as $test) {
            foreach (range(1, 10) as $number) {
                Question::create([
                    'test_id' => $test->id,
                    'question' => 'Question '.$number,
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'A',
                ]);
            }
        }

        Result::create(['user_id' => $student->id, 'test_id' => $removedTest->id, 'score' => 4, 'total_questions' => 10, 'answers' => array_fill(0, 10, ['is_correct' => true])]);
        Result::create(['user_id' => $student->id, 'test_id' => $remainingTest->id, 'score' => 2, 'total_questions' => 10, 'answers' => array_fill(0, 10, ['is_correct' => true])]);

        $this->actingAs($admin)
            ->delete(route('admin.subtopics.destroy', $removedSubtopic))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $student->id, 'total_points' => 6]);
        $this->assertDatabaseMissing('user_achievements', ['user_id' => $student->id, 'achievement_id' => $achievement->id]);
    }

    public function test_student_points_ignore_quizzes_left_by_deleted_subtopics(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'total_points' => 18]);
        $module = Module::create(['department_id' => $department->id, 'title' => 'Networking']);
        Subtopic::create(['module_id' => $module->id, 'title' => 'Remaining topic']);
        $remainingTest = Test::create(['department_id' => $department->id, 'title' => 'Remaining topic - Review Quiz', 'topic' => 'Remaining topic']);
        $orphanTest = Test::create(['department_id' => $department->id, 'title' => 'Deleted topic - Review Quiz', 'topic' => 'Deleted topic']);

        foreach ([$remainingTest, $orphanTest] as $test) {
            Question::create([
                'test_id' => $test->id,
                'question' => 'Question',
                'options' => ['A', 'B'],
                'correct_answer' => 'A',
            ]);
        }

        Result::create(['user_id' => $student->id, 'test_id' => $remainingTest->id, 'score' => 1, 'total_questions' => 1, 'answers' => [['is_correct' => true]]]);
        Result::create(['user_id' => $student->id, 'test_id' => $orphanTest->id, 'score' => 1, 'total_questions' => 1, 'answers' => [['is_correct' => true]]]);

        $this->actingAs($student)
            ->get(route('student.achievements'))
            ->assertViewHas('user', fn (User $user): bool => $user->total_points === 30);
    }
}
