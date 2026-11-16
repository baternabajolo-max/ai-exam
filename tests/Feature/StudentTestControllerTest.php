<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Test as Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentTestControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_cannot_access_an_exam_before_activation_or_after_closing(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        [$department, $course] = $this->createProgram();
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($department->id, $course->id, '2026-03-20 11:00:00', '2026-03-20 12:00:00');

        $this->actingAs($student)
            ->get(route('student.tests.show', $exam))
            ->assertForbidden();
        $this->getJson(route('student.tests.build', $exam))->assertForbidden();

        $this->travelTo('2026-03-20 11:00:00');
        $this->get(route('student.tests.show', $exam))
            ->assertOk()
            ->assertSee('Scheduled Network Question');
        $this->getJson(route('student.tests.build', $exam))
            ->assertOk()
            ->assertJson(['status' => 'already_generated']);

        $this->travelTo('2026-03-20 12:00:01');
        $this->get(route('student.tests.show', $exam))->assertForbidden();
        $this->post(route('student.tests.submit', $exam), [])->assertForbidden();
    }

    public function test_student_cannot_access_a_scheduled_exam_from_another_program(): void
    {
        $this->travelTo('2026-03-20 11:30:00');
        [$department, $course] = $this->createProgram();
        $otherCourse = Course::create([
            'name' => 'Information Systems',
            'code' => 'IS',
            'department_id' => $department->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $otherCourse->id,
        ]);
        $exam = $this->createExam($department->id, $course->id, '2026-03-20 11:00:00', '2026-03-20 12:00:00');

        $this->actingAs($student)
            ->get(route('student.tests.show', $exam))
            ->assertNotFound();
    }

    public function test_exam_submission_records_question_identifiers_for_item_analysis(): void
    {
        $this->travelTo('2026-03-20 11:30:00');
        [$department, $course] = $this->createProgram();
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Which protocol protects a website?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'HTTP'],
            'correct_answer' => 'HTTPS',
            'topic' => 'Network security',
        ]);
        $exam = $this->createExam($department->id, $course->id, '2026-03-20 11:00:00', '2026-03-20 12:00:00');
        $question = Question::create([
            'test_id' => $exam->id,
            'question_bank_item_id' => $item->id,
            'question_type' => Exam::QUESTION_TYPE_MULTIPLE_CHOICE,
            'question' => $item->question,
            'options' => $item->options,
            'correct_answer' => $item->correct_answer,
            'topic' => $item->topic,
        ]);

        $this->actingAs($student)
            ->post(route('student.tests.submit', $exam), [
                'answers' => [$question->id => 'HTTPS'],
                'question_order' => [$question->id],
            ])
            ->assertRedirect();

        $result = $student->results()->where('test_id', $exam->id)->firstOrFail();
        $this->assertSame($question->id, $result->answers[0]['question_id']);
        $this->assertSame($item->id, $result->answers[0]['question_bank_item_id']);
        $this->assertTrue($result->answers[0]['is_correct']);
    }

    public function test_student_can_submit_a_scheduled_exam_once_and_return_to_examinations_after_results(): void
    {
        $this->travelTo('2026-03-20 11:30:00');
        [$department, $course] = $this->createProgram();
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($department->id, $course->id, '2026-03-20 11:00:00', '2026-03-20 12:00:00');
        $exam->update(['time_limit' => 60]);
        $question = $exam->questions()->firstOrFail();

        $this->actingAs($student)
            ->get(route('student.tests.show', $exam))
            ->assertSee('role="timer"', false)
            ->assertSee('data-remaining-seconds="3600"', false)
            ->assertSee('01:00:00')
            ->assertSee('position: sticky', false)
            ->assertSee("addEventListener('pagehide'", false)
            ->assertSee('navigator.sendBeacon(form.action, submission)', false)
            ->assertDontSee('Exit Quiz');

        $this->travel(10)->seconds();
        $this->get(route('student.tests.show', $exam))
            ->assertSee('data-remaining-seconds="3590"', false)
            ->assertSee('00:59:50');

        $this->actingAs($student)
            ->post(route('student.tests.submit', $exam), [
                'answers' => [$question->id => 'A'],
                'question_order' => [$question->id],
            ])
            ->assertRedirect();

        $result = $student->results()->where('test_id', $exam->id)->firstOrFail();
        $this->assertSame(1, $result->score);
        $this->assertDatabaseCount('results', 1);

        $this->post(route('student.tests.submit', $exam), [
            'answers' => [$question->id => 'B'],
            'question_order' => [$question->id],
        ])->assertRedirect(route('student.results.show', $result));
        $this->assertDatabaseCount('results', 1);
        $this->assertSame(1, $result->fresh()->score);

        $this->get(route('student.tests.show', $exam))
            ->assertRedirect(route('student.results.show', $result));
        $this->getJson(route('student.tests.build', $exam))
            ->assertStatus(409)
            ->assertJsonPath('status', 'already_completed')
            ->assertJsonPath('result_url', route('student.results.show', $result));

        $this->get(route('student.results.show', $result))
            ->assertSee('Return to Examination')
            ->assertSee('href="'.route('student.exams.index').'"', false)
            ->assertDontSee('href="'.route('student.learn.index').'"', false);

        $this->travelTo('2026-03-20 12:01:00');
        $this->get(route('student.tests.show', $exam))
            ->assertRedirect(route('student.results.show', $result));
        $this->getJson(route('student.tests.build', $exam))
            ->assertStatus(409)
            ->assertJsonPath('status', 'already_completed');

        $this->get(route('student.exams.index'))
            ->assertSee('Completed')
            ->assertSee('View results')
            ->assertDontSee('Open examination');
    }

    public function test_scheduled_exam_submission_after_the_countdown_expires_is_saved_as_unanswered(): void
    {
        $this->travelTo('2026-03-20 11:30:00');
        [$department, $course] = $this->createProgram();
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($department->id, $course->id, '2026-03-20 11:00:00', '2026-03-20 12:00:00');
        $exam->update(['time_limit' => 1]);
        $question = $exam->questions()->firstOrFail();

        $this->actingAs($student)
            ->get(route('student.tests.show', $exam))
            ->assertSee('00:01:00');

        $this->travel(61)->seconds();
        $this->post(route('student.tests.submit', $exam), [
            'answers' => [$question->id => 'A'],
            'question_order' => [$question->id],
        ])->assertRedirect();

        $result = $student->results()->where('test_id', $exam->id)->firstOrFail();
        $this->assertSame(0, $result->score);
        $this->assertNull($result->answers[0]['user_answer']);
        $this->assertFalse($result->answers[0]['is_correct']);
    }

    public function test_student_exam_list_displays_status_and_only_assigned_program_exams(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        [$department, $course] = $this->createProgram();
        $otherCourse = Course::create([
            'name' => 'Information Systems',
            'code' => 'IS',
            'department_id' => $department->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $scheduledExam = $this->createExam($department->id, $course->id, '2026-03-20 11:00:00', '2026-03-20 12:00:00', 'Program Exam');
        $this->createExam($department->id, $otherCourse->id, '2026-03-20 11:00:00', null, 'Other Program Exam');

        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee($scheduledExam->title)
            ->assertSee('Scheduled')
            ->assertDontSee('Other Program Exam');
    }

    public function test_student_can_take_exam_after_its_displayed_local_opening_time(): void
    {
        $this->travelTo('2026-03-20 17:11:00');
        [$department, $course] = $this->createProgram();
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam(
            $department->id,
            $course->id,
            '2026-03-20 17:10:00',
            '2026-03-20 18:10:00',
            'Local time examination',
        );

        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertSee('Local time examination')
            ->assertSee('Take exam')
            ->assertSee('Mar 20, 2026 5:10 PM');

        $this->get(route('student.tests.show', $exam))
            ->assertOk()
            ->assertSee('Scheduled Network Question');
    }

    public function test_student_must_confirm_before_taking_an_active_exam(): void
    {
        $this->travelTo('2026-03-20 11:30:00');
        [$department, $course] = $this->createProgram();
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($department->id, $course->id, '2026-03-20 11:00:00', '2026-03-20 12:00:00', 'Network Midterm');
        $exam->update(['time_limit' => 60]);

        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertSee('Take exam')
            ->assertSee('data-bs-toggle="modal"', false)
            ->assertSee('data-bs-target="#takeExamConfirmation"', false)
            ->assertSee('data-exam-start-url="'.route('student.exams.start', $exam).'"', false)
            ->assertSee('id="takeExamConfirmation"', false)
            ->assertSee('only once')
            ->assertSee('will not be submitted until you finish')
            ->assertSee('Cancel')
            ->assertSee('Proceed')
            ->assertDontSee('Open examination');

        $this->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.tests.show', $exam));
        $this->assertDatabaseCount('results', 0);

        $this->get(route('student.tests.show', $exam))
            ->assertSee('01:00:00')
            ->assertSee('Assessment In Progress');
        $this->assertDatabaseCount('results', 0);
    }

    /**
     * @return array{Department, Course}
     */
    private function createProgram(): array
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create([
            'name' => 'Software Engineering',
            'code' => 'SE',
            'department_id' => $department->id,
        ]);

        return [$department, $course];
    }

    private function createExam(
        int $departmentId,
        int $courseId,
        string $availableFrom,
        ?string $availableUntil,
        string $title = 'Network Security Examination',
    ): Exam {
        $exam = Exam::create([
            'title' => $title,
            'topic' => 'Network security',
            'department_id' => $departmentId,
            'course_id' => $courseId,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'available_from' => $availableFrom,
            'available_until' => $availableUntil,
            'question_type' => Exam::QUESTION_TYPE_MULTIPLE_CHOICE,
        ]);
        Question::create([
            'test_id' => $exam->id,
            'question' => 'Scheduled Network Question',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);

        return $exam;
    }
}
