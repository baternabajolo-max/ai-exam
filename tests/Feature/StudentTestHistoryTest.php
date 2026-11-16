<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Result;
use App\Models\Test as StudentTest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentTestHistoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_shows_only_recent_general_test_attempts(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);

        foreach (range(1, 11) as $attemptNumber) {
            $test = StudentTest::create([
                'title' => ($attemptNumber % 2 === 0 ? 'General Test' : 'Review Quiz')." Attempt {$attemptNumber}",
                'department_id' => $department->id,
                'is_general' => $attemptNumber % 2 === 0,
            ]);

            $result = Result::create([
                'user_id' => $student->id,
                'test_id' => $test->id,
                'score' => 1,
                'total_questions' => 1,
                'passed' => true,
                'answers' => [['question_text' => 'Question', 'is_correct' => true]],
            ]);
            $result->created_at = now()->addSeconds($attemptNumber);
            $result->updated_at = now()->addSeconds($attemptNumber);
            $result->save();
        }

        $response = $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertViewHas('testHistory');
        $history = $response->viewData('testHistory');
        $titles = $history->map(static fn (Result $result): ?string => $result->test?->title)->all();

        $this->assertCount(5, $history);
        $this->assertContains('General Test Attempt 10', $titles);
        $this->assertNotContains('Review Quiz Attempt 11', $titles);
        $this->assertNotContains('Review Quiz Attempt 1', $titles);
        $response->assertSee('Recent General Tests')
            ->assertSee('Subtopic quiz history is available in Study Activity.')
            ->assertSee('activity-column-chart')
            ->assertDontSee('Review Quiz Attempt 11');
    }

    public function test_full_history_paginates_twenty_attempts_per_page(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);

        foreach (range(1, 21) as $attemptNumber) {
            $test = StudentTest::create([
                'title' => "General Test Attempt {$attemptNumber}",
                'department_id' => $department->id,
                'is_general' => true,
            ]);

            Result::create([
                'user_id' => $student->id,
                'test_id' => $test->id,
                'score' => 1,
                'total_questions' => 1,
                'passed' => true,
                'answers' => [],
            ]);
        }
        $reviewQuiz = StudentTest::create([
            'title' => 'Topic Review Quiz',
            'department_id' => $department->id,
            'is_general' => false,
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $reviewQuiz->id,
            'score' => 1,
            'total_questions' => 1,
            'passed' => true,
            'answers' => [],
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.results.index'))
            ->assertSee('My General Test Results History');

        $response->assertDontSee('Topic Review Quiz')
            ->assertViewHas('results', fn ($results): bool => $results->perPage() === 20
                && $results->count() === 20
                && $results->total() === 21);
    }

    public function test_general_test_result_links_to_a_multiple_choice_retake(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $test = StudentTest::create([
            'title' => 'Identification General Test',
            'department_id' => $department->id,
            'is_general' => true,
            'question_type' => StudentTest::QUESTION_TYPE_IDENTIFICATION,
        ]);
        $result = Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 1,
            'total_questions' => 1,
            'passed' => true,
            'answers' => [[
                'question_text' => 'Plants use light to produce ____.',
                'options' => [],
                'user_answer' => 'GLUCOSE',
                'correct_answer' => 'GLUCOSE',
                'is_correct' => true,
                'explanation' => 'Glucose stores chemical energy.',
            ]],
        ]);

        $this->actingAs($student)
            ->get(route('student.results.show', $result))
            ->assertSee('Take test again')
            ->assertSee(route('student.tests.generate-general'), false);
    }
}
