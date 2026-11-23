<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Result;
use App\Models\Test as Exam;
use App\Models\TestRetakeGrant;
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
            ->assertSee("addEventListener('visibilitychange'", false)
            ->assertSee('Switching tabs, closing the tab, or leaving this page submits your current answers automatically.')
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
            ->assertSee('data-close-exam-tab', false)
            ->assertSee("new BroadcastChannel('lemu-exam-updates')", false)
            ->assertSee("type: 'exam-submitted'", false)
            ->assertSee('window.opener.location.reload();', false)
            ->assertSee('window.close();', false)
            ->assertSee('window.location.assign(returnLink.href);', false)
            ->assertDontSee('href="'.route('student.learn.index').'"', false);

        $this->travelTo('2026-03-20 12:01:00');
        $this->get(route('student.tests.show', $exam))
            ->assertRedirect(route('student.results.show', $result));
        $this->getJson(route('student.tests.build', $exam))
            ->assertStatus(409)
            ->assertJsonPath('status', 'already_completed');

        $this->get(route('student.exams.index'))
            ->assertSee('Your assessments')
            ->assertSee('0 available · 1 taken')
            ->assertSee('Taken Exams')
            ->assertSee('Date taken')
            ->assertSee($result->created_at->format('M j, Y g:i A'))
            ->assertSee('Score')
            ->assertSee($result->score.' / '.$result->total_questions)
            ->assertSee('Completed')
            ->assertSee('View results')
            ->assertDontSee('Open examination');
    }

    public function test_student_can_take_one_activated_retake_with_a_persistent_fresh_timer(): void
    {
        $this->travelTo('2026-03-20 11:30:00');
        [$department, $course] = $this->createProgram();
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($department->id, $course->id, '2026-03-20 11:00:00', '2026-03-20 12:00:00');
        $exam->update(['time_limit' => 60]);
        $question = $exam->questions()->firstOrFail();

        $this->actingAs($student)
            ->post(route('student.tests.submit', $exam), [
                'answers' => [$question->id => 'A'],
                'question_order' => [$question->id],
            ])
            ->assertRedirect();
        $firstResult = $student->results()->where('test_id', $exam->id)->firstOrFail();

        $this->actingAs($faculty)
            ->post(route('admin.students.tests.retake', ['student' => $student, 'test' => $exam]))
            ->assertRedirect();

        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertSee('Retake available')
            ->assertSee('student-exams__status--retake', false)
            ->assertSee('Take retake')
            ->assertSee('View results');

        $this->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.tests.show', $exam));
        $grant = TestRetakeGrant::query()
            ->where('test_id', $exam->id)
            ->where('student_id', $student->id)
            ->firstOrFail();
        $this->assertSame(now()->timestamp, $grant->started_at->timestamp);

        $this->get(route('student.tests.show', $exam))
            ->assertSee('data-remaining-seconds="3600"', false);
        $this->travel(10)->seconds();
        $this->get(route('student.tests.show', $exam))
            ->assertSee('data-remaining-seconds="3590"', false);

        $this->post(route('student.tests.submit', $exam), [
            'answers' => [$question->id => 'B'],
            'question_order' => [$question->id],
        ])->assertRedirect();

        $latestResult = $student->results()
            ->where('test_id', $exam->id)
            ->latest('id')
            ->firstOrFail();
        $this->assertNotSame($firstResult->id, $latestResult->id);
        $this->assertDatabaseCount('results', 2);
        $this->assertDatabaseHas('test_retake_grants', [
            'test_id' => $exam->id,
            'student_id' => $student->id,
        ]);
        $this->assertNotNull($grant->fresh()->consumed_at);

        $this->get(route('student.tests.show', $exam))
            ->assertRedirect(route('student.results.show', $latestResult));
        $this->getJson(route('student.tests.build', $exam))
            ->assertStatus(409)
            ->assertJsonPath('status', 'already_completed')
            ->assertJsonPath('result_url', route('student.results.show', $latestResult));
        $this->get(route('student.exams.index'))
            ->assertSee('View results')
            ->assertDontSee('Take retake');
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
        $this->assertSame('time_expired', $result->submission_type);
        $this->assertSame(60, $result->time_taken_seconds);
        $this->assertSame(now()->subSeconds(61)->timestamp, $result->started_at->timestamp);
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
            ->assertSee('Available Exams')
            ->assertSee('Taken Exams')
            ->assertSee('Due date')
            ->assertSee($scheduledExam->available_until->format('M j, Y g:i A'))
            ->assertSee($course->name)
            ->assertSee('Scheduled')
            ->assertDontSee('Other Program Exam');
    }

    public function test_student_exam_list_separates_missed_exams_from_available_and_taken_exams(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        [$department, $course] = $this->createProgram();
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $noDeadlineExam = $this->createExam($department->id, $course->id, '2026-03-20 07:00:00', null, 'No deadline exam');
        $availableExam = $this->createExam($department->id, $course->id, '2026-03-20 08:00:00', '2026-03-20 11:00:00', 'Available exam');
        $deadlineNowExam = $this->createExam($department->id, $course->id, '2026-03-20 09:00:00', '2026-03-20 10:00:00', 'Deadline now exam');
        $upcomingExam = $this->createExam($department->id, $course->id, '2026-03-20 11:00:00', '2026-03-20 12:00:00', 'Upcoming exam');
        $missedExam = $this->createExam($department->id, $course->id, '2026-03-20 06:00:00', '2026-03-20 09:59:00', 'Missed exam');
        $takenExam = $this->createExam($department->id, $course->id, '2026-03-20 06:00:00', '2026-03-20 09:00:00', 'Taken exam');
        Result::create([
            'user_id' => $student->id,
            'test_id' => $takenExam->id,
            'score' => 1,
            'total_questions' => 1,
            'answers' => [],
            'created_at' => '2026-03-20 08:30:00',
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('Missed Exams')
            ->assertSee('Missed exam')
            ->assertSee('1 missed')
            ->assertSee('No submission was recorded before the deadline.');

        $this->assertSame(
            [$noDeadlineExam->id, $availableExam->id, $deadlineNowExam->id, $upcomingExam->id],
            $response->viewData('availableExams')->modelKeys(),
        );
        $this->assertSame([$missedExam->id], $response->viewData('missedExams')->modelKeys());
        $this->assertSame([$takenExam->id], $response->viewData('takenExams')->modelKeys());
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
        $question = $exam->questions()->firstOrFail();
        $question->update(['image_path' => 'question-bank-images/exam-equation.png']);

        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertSee('Local time examination')
            ->assertSee('Take exam')
            ->assertSee('Mar 20, 2026 5:10 PM');

        $this->get(route('student.tests.show', $exam))
            ->assertOk()
            ->assertSee('Scheduled Network Question')
            ->assertSee('src="'.asset('storage/'.$question->image_path).'"', false);
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
            ->assertSee('id="startExamForm"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('bootstrap.Modal.getOrCreateInstance(confirmationModal).hide();', false)
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
