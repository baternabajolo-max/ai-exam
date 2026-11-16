<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Test as Exam;
use App\Models\User;
use App\Notifications\FacultyExamApprovalRequestedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TestControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_selects_a_program_before_opening_the_examination_modal(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'What protects web traffic?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'HTTP'],
            'correct_answer' => 'HTTPS',
            'topic' => 'Network security',
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.tests.create', ['course_id' => $course->id]))
            ->assertSee('data-bs-target="#createExamModal"', false)
            ->assertSee('id="createExamModal"', false)
            ->assertSee('name="title"', false)
            ->assertSee('name="question_count"', false)
            ->assertSee('id="examQuestionIds"', false)
            ->assertSee("input.name = 'question_bank_item_ids[]';", false)
            ->assertSee('Choose random questions automatically')
            ->assertSee($faculty->name."'s bank", false)
            ->assertSee($faculty->name);
    }

    public function test_faculty_can_select_a_program_and_prepare_an_exam_from_all_banks_in_their_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $otherCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $otherFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $ownItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Question from my bank?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);
        $sharedItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $otherFaculty->id,
            'question' => 'Question from another Faculty bank?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'B',
        ]);
        QuestionBankItem::create([
            'course_id' => $otherCourse->id,
            'author_id' => $otherFaculty->id,
            'question' => 'Question from another college?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'C',
        ]);

        $this->actingAs($faculty)
            ->get(route('admin.tests.create', ['course_id' => $course->id]))
            ->assertSee('action="'.route('admin.tests.create').'"', false)
            ->assertSee('id="exam-course-form"', false)
            ->assertDontSee('Load questions')
            ->assertSee('Create examination')
            ->assertSee(route('admin.tests.store-manual'), false)
            ->assertSee('All 2 questions in this program are available')
            ->assertSee('name="question_count"', false)
            ->assertSee('Choose random questions automatically')
            ->assertSee($ownItem->question)
            ->assertSee($sharedItem->question)
            ->assertDontSee('Question from another college?')
            ->assertSee('Suggestion: schedule at least two days ahead')
            ->assertSee('Requires Admin approval')
            ->assertSee('Post exam')
            ->assertDontSee('Post exam for Admin approval')
            ->assertSee('id="available-until"', false)
            ->assertSee('The closing date and time must be after the start date and time.');

        $this->get(route('admin.tests.create', ['course_id' => $otherCourse->id]))
            ->assertNotFound();
    }

    public function test_faculty_submits_an_exam_using_another_facultys_question_as_a_pending_read_only_snapshot(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $questionOwner = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $sharedItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $questionOwner->id,
            'question' => 'Can the Faculty reuse this question?',
            'options' => ['Yes', 'No', 'Maybe', 'Not sure'],
            'correct_answer' => 'Yes',
            'topic' => 'Shared questions',
        ]);

        $this->actingAs($faculty)
            ->post(route('admin.tests.store-manual'), [
                'course_id' => $course->id,
                'title' => 'Faculty examination request',
                'topic' => 'Shared questions',
                'available_from' => '2026-03-20T10:30',
                'available_until' => '2026-03-20T13:00',
                'time_limit' => 60,
                'question_count' => '1',
                'question_bank_item_ids' => [$sharedItem->id],
            ])
            ->assertRedirect(route('admin.tests.index'))
            ->assertSessionHas('success', 'Examination request submitted for Admin approval.');

        $exam = Exam::query()->where('title', 'Faculty examination request')->firstOrFail();
        $examQuestion = $exam->questions()->firstOrFail();
        $this->assertDatabaseHas('tests', [
            'id' => $exam->id,
            'created_by' => $faculty->id,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => 'pending',
        ]);
        $this->assertSame($sharedItem->id, $examQuestion->question_bank_item_id);
        $this->assertSame($sharedItem->question, $examQuestion->question);
        $this->assertSame($sharedItem->options, $examQuestion->options);
        $this->assertSame($sharedItem->correct_answer, $examQuestion->correct_answer);
        $this->assertSame('Shared questions', $examQuestion->topic);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
            'type' => FacultyExamApprovalRequestedNotification::class,
        ]);
        $this->assertSame(
            route('super-admin.tests.index'),
            $admin->notifications()->firstOrFail()->data['url'],
        );

        $this->actingAs($admin)
            ->get(route('super-admin.tests.index'))
            ->assertSee('Examination approval requested')
            ->assertSee($faculty->name.' submitted '.$exam->title.' for approval.')
            ->assertSee('Review examination');

        $this->actingAs($faculty)
            ->get(route('admin.tests.index'))
            ->assertSee('Faculty examination request')
            ->assertSee('Pending approval')
            ->assertDontSee('Approve');
    }

    public function test_admin_can_publish_an_exam_using_questions_from_multiple_faculty_banks(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $otherFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'What protects web traffic?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'HTTP'],
            'correct_answer' => 'HTTPS',
            'topic' => 'Network security',
        ]);
        $otherItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $otherFaculty->id,
            'question' => 'Which protocol transfers web pages?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'IMAP'],
            'correct_answer' => 'HTTPS',
            'topic' => 'Network security',
        ]);

        $this->actingAs($admin)
            ->post(route('super-admin.tests.store-manual'), [
                'course_id' => $course->id,
                'title' => 'Network Security Midterm',
                'topic' => 'Network security',
                'description' => 'Answer every question.',
                'available_from' => '2026-03-20T11:00',
                'available_until' => '2026-03-20T13:00',
                'time_limit' => 60,
                'question_count' => 2,
                'question_bank_item_ids' => [$item->id, $otherItem->id],
            ])
            ->assertRedirect(route('super-admin.tests.index'))
            ->assertSessionHas('success');

        $exam = Exam::query()->where('title', 'Network Security Midterm')->firstOrFail();
        $this->assertDatabaseHas('tests', [
            'id' => $exam->id,
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $admin->id,
            'is_scheduled_exam' => true,
            'is_published' => true,
        ]);
        $this->assertSame('Scheduled', $exam->scheduleStatus());
        $this->assertSame(2, $exam->questions()->count());
        $questionIds = $exam->questions()->pluck('question_bank_item_id')->all();
        sort($questionIds);

        $this->assertSame(
            collect([$item->id, $otherItem->id])->sort()->values()->all(),
            $questionIds,
        );
    }

    public function test_admin_cannot_publish_an_exam_using_another_programs_question(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $otherCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $otherFaculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $otherDepartment->id,
            'course_id' => $otherCourse->id,
        ]);
        $item = QuestionBankItem::create([
            'course_id' => $otherCourse->id,
            'author_id' => $otherFaculty->id,
            'question' => 'Foreign question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);

        $this->actingAs($admin)
            ->post(route('super-admin.tests.store-manual'), [
                'course_id' => $course->id,
                'title' => 'Invalid examination',
                'topic' => 'Topic',
                'available_from' => now()->toDateTimeLocalString(),
                'time_limit' => 60,
                'question_count' => 1,
                'question_bank_item_ids' => [$item->id],
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('tests', ['title' => 'Invalid examination']);
    }

    public function test_admin_cannot_create_an_exam_that_closes_before_it_opens(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);

        $this->actingAs($admin)
            ->post(route('super-admin.tests.store-manual'), [
                'course_id' => $course->id,
                'title' => 'Invalid window',
                'topic' => 'Topic',
                'available_from' => '2026-03-20T11:00',
                'available_until' => '2026-03-20T10:00',
                'time_limit' => 60,
                'question_count' => 1,
                'question_bank_item_ids' => [$item->id],
            ])
            ->assertSessionHasErrors('available_until');

        $this->assertDatabaseMissing('tests', ['title' => 'Invalid window']);
    }

    public function test_faculty_must_select_exactly_the_requested_number_of_questions(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Question from this program?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);

        $this->actingAs($faculty)
            ->post(route('admin.tests.store-manual'), [
                'course_id' => $course->id,
                'title' => 'Question count validation',
                'topic' => 'Topic',
                'available_from' => '2026-03-22T10:00',
                'time_limit' => 60,
                'question_count' => 2,
                'question_bank_item_ids' => [$item->id],
            ])
            ->assertSessionHasErrors([
                'question_bank_item_ids' => 'Select exactly the number of questions you entered.',
            ]);

        $this->assertDatabaseMissing('tests', ['title' => 'Question count validation']);
    }

    public function test_faculty_can_schedule_an_exam_for_today_and_sees_a_two_day_scheduling_note(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Question for a scheduled exam?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'topic' => 'Topic',
        ]);

        $this->actingAs($faculty)
            ->get(route('admin.tests.create', ['course_id' => $course->id]))
            ->assertSee('Suggestion: schedule at least two days ahead')
            ->assertDontSee('min="2026-03-22T10:00"', false);

        $this->post(route('admin.tests.store-manual'), [
            'course_id' => $course->id,
            'title' => 'Same day examination',
            'topic' => 'Topic',
            'available_from' => '2026-03-20T10:30',
            'time_limit' => 60,
            'question_count' => 1,
            'question_bank_item_ids' => [$item->id],
        ])
            ->assertRedirect(route('admin.tests.index'))
            ->assertSessionHas('success', 'Examination request submitted for Admin approval.');

        $this->assertDatabaseHas('tests', [
            'title' => 'Same day examination',
            'approval_status' => 'pending',
        ]);
    }

    public function test_faculty_can_create_requests_but_cannot_submit_to_the_admin_only_route(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $exam = Exam::create([
            'title' => 'Published examination',
            'topic' => 'Topic',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'approval_status' => Exam::APPROVAL_STATUS_APPROVED,
            'available_from' => now()->addHour(),
        ]);
        $analysisUrl = route('admin.item-analysis.index', ['test_id' => $exam->id]);

        $this->actingAs($faculty)
            ->get(route('admin.tests.index'))
            ->assertSee('href="'.route('admin.tests.create').'"', false)
            ->assertSee('Create Examination')
            ->assertSee('Analyze')
            ->assertSee('href="'.e($analysisUrl).'"', false);

        $this->post(route('super-admin.tests.store-manual'), [])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_approve_a_faculty_exam_and_students_only_see_it_after_approval(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $bankItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Question awaiting Admin review?',
            'options' => ['Approve', 'Reject', 'Wait', 'Skip'],
            'correct_answer' => 'Approve',
        ]);
        $exam = Exam::create([
            'title' => 'Pending examination',
            'topic' => 'Topic',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => 'pending',
            'available_from' => now()->subHour(),
        ]);
        Question::create([
            'test_id' => $exam->id,
            'question_bank_item_id' => $bankItem->id,
            'question' => $bankItem->question,
            'options' => $bankItem->options,
            'correct_answer' => $bankItem->correct_answer,
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.tests.index'))
            ->assertSee('Review request')
            ->assertSee('id="reviewExamModal'.$exam->id.'"', false)
            ->assertSee('Question awaiting Admin review?')
            ->assertSee('Correct answer')
            ->assertSee('Question from '.$faculty->name."'s bank", false);

        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertDontSee($exam->title);

        $this->actingAs($admin)
            ->post(route('super-admin.tests.approve', $exam))
            ->assertRedirect()
            ->assertSessionHas('success', 'Examination approved and published.');

        $this->assertDatabaseHas('tests', [
            'id' => $exam->id,
            'approval_status' => 'approved',
            'is_published' => true,
            'reviewed_by' => $admin->id,
        ]);
        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertSee($exam->title);
    }

    public function test_admin_can_reject_a_faculty_exam_without_publishing_it(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $exam = Exam::create([
            'title' => 'Rejected examination',
            'topic' => 'Topic',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('super-admin.tests.reject', $exam))
            ->assertRedirect()
            ->assertSessionHas('success', 'Examination request rejected.');

        $this->assertDatabaseHas('tests', [
            'id' => $exam->id,
            'approval_status' => 'rejected',
            'is_published' => false,
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_combined_examination_page_has_analysis_action_for_each_exam(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $exam = Exam::create([
            'title' => 'Network Security Midterm',
            'topic' => 'Network security',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $admin->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'available_from' => now()->addHour(),
        ]);
        $analysisUrl = route('super-admin.item-analysis.index', ['course_id' => $course->id, 'test_id' => $exam->id]);

        $this->actingAs($admin)
            ->get(route('super-admin.tests.index'))
            ->assertSee('Examinations & Analysis')
            ->assertSee('Analyze')
            ->assertSee('href="'.e($analysisUrl).'"', false)
            ->assertDontSee('href="'.route('super-admin.item-analysis.index').'"', false);
    }
}
