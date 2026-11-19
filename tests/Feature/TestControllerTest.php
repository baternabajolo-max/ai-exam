<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Test as Exam;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\FacultyExamApprovalDecisionNotification;
use App\Notifications\FacultyExamApprovalRequestedNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TestControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_exam_builder_disables_program_selection_until_a_college_is_selected(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);

        $response = $this->actingAs($admin)
            ->get(route('super-admin.tests.create'))
            ->assertSee('id="exam-department"', false)
            ->assertSee('name="department_id"', false)
            ->assertSee('Choose a college')
            ->assertSee('id="exam-course"', false)
            ->assertSee('Choose a college first')
            ->assertDontSee($course->name);

        $this->assertSame(
            1,
            preg_match('/<select(?=[^>]*\bid="exam-course")(?=[^>]*\bdisabled\b)[^>]*>/', $response->getContent()),
        );
    }

    public function test_exam_builder_shows_only_programs_in_the_selected_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $otherCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);

        $response = $this->actingAs($admin)
            ->get(route('super-admin.tests.create', ['department_id' => $department->id]))
            ->assertSee('id="exam-course"', false)
            ->assertSee('value="'.$course->id.'"', false)
            ->assertSee($course->name)
            ->assertDontSee($otherCourse->name)
            ->assertViewHas('courses', fn ($courses): bool => $courses->count() === 1
                && $courses->first()->id === $course->id)
            ->assertViewHas('selectedDepartmentId', $department->id);

        $this->assertSame(
            0,
            preg_match('/<select(?=[^>]*\bid="exam-course")(?=[^>]*\bdisabled\b)[^>]*>/', $response->getContent()),
        );
    }

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
            'difficulty' => 'moderate',
        ]);
        QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Which protocol provides a secure transport channel?',
            'options' => ['TLS', 'FTP', 'SMTP', 'HTTP'],
            'correct_answer' => 'TLS',
            'topic' => 'Network security',
            'difficulty' => 'easy',
        ]);
        QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Which protocol transfers web pages?',
            'options' => ['HTTP', 'FTP', 'SMTP', 'DNS'],
            'correct_answer' => 'HTTP',
            'topic' => 'Web protocols',
            'difficulty' => 'hard',
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.tests.create', ['course_id' => $course->id]))
            ->assertSee('data-bs-target="#createExamModal"', false)
            ->assertSee('id="createExamModal"', false)
            ->assertSee('name="title"', false)
            ->assertSee('exam-builder__question-picker', false)
            ->assertSee('name="question_bank_item_ids[]"', false)
            ->assertSee('id="auto-pick-topic"', false)
            ->assertSee('id="auto-pick-difficulty"', false)
            ->assertSee('id="auto-pick-count"', false)
            ->assertSee('exam-builder__pool-summary', false)
            ->assertSee('Total Available')
            ->assertSee('3 questions')
            ->assertSee('across 2 topics')
            ->assertSee('Difficulty Breakdown')
            ->assertSee('1 Easy · 1 Moderate · 1 Hard')
            ->assertSee('exam-builder__pool-difficulty--easy', false)
            ->assertSee('exam-builder__pool-difficulty--moderate', false)
            ->assertSee('exam-builder__pool-difficulty--hard', false)
            ->assertSee('Selected for Exam')
            ->assertSee('id="modal-selected-question-count"', false)
            ->assertSee('id="modal-selected-question-breakdown"', false)
            ->assertSee('0 selected')
            ->assertSee('All Topics')
            ->assertDontSee('All Topics (3)')
            ->assertSee('Network security (2 items)')
            ->assertSee('Web protocols (1 item)')
            ->assertSee('Auto-Pick')
            ->assertSee('Clear selection')
            ->assertDontSee('exam-builder__step-nav', false)
            ->assertDontSee('Subject or topic')
            ->assertDontSee('name="topic"', false)
            ->assertDontSee('name="question_count"', false)
            ->assertSee($faculty->name."'s bank", false)
            ->assertSee($faculty->name);
    }

    public function test_faculty_can_prepare_an_exam_from_any_program_in_their_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $anotherSameCollegeCourse = Course::create(['name' => 'Computer Science', 'code' => 'CS', 'department_id' => $department->id]);
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
            ->assertDontSee('id="exam-department"', false)
            ->assertDontSee('name="department_id"', false)
            ->assertSee($anotherSameCollegeCourse->name)
            ->assertDontSee('Load questions')
            ->assertSee('Create examination')
            ->assertSee(route('admin.tests.store-manual'), false)
            ->assertSee('All 2 questions in this program are available')
            ->assertSee('exam-builder__question-picker', false)
            ->assertSee('exam-builder__pool-summary', false)
            ->assertSee('Selected for Exam')
            ->assertSee('name="question_bank_item_ids[]"', false)
            ->assertSee('id="auto-pick-topic"', false)
            ->assertSee('Auto-Pick')
            ->assertDontSee('exam-builder__step-nav', false)
            ->assertDontSee('Subject or topic')
            ->assertSee($ownItem->question)
            ->assertSee($sharedItem->question)
            ->assertDontSee('Question from another college?')
            ->assertSee('Suggestion: schedule at least two days ahead')
            ->assertSee('Requires Admin approval')
            ->assertSee('Submit for approval')
            ->assertSee('Students can access it after Admin approval.')
            ->assertSee('id="available-until"', false)
            ->assertSee('The closing date and time must be after the start date and time.');

        $this->get(route('admin.tests.create', ['course_id' => $otherCourse->id]))
            ->assertNotFound();
        $this->get(route('admin.tests.create', ['course_id' => $anotherSameCollegeCourse->id]))
            ->assertSee($anotherSameCollegeCourse->name)
            ->assertViewHas('selectedCourseId', $anotherSameCollegeCourse->id);
    }

    public function test_disabled_question_bank_items_are_hidden_and_rejected_when_creating_exams(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $activeItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Enabled question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);
        $disabledItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Disabled question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'is_active' => false,
        ]);

        $this->actingAs($faculty)
            ->get(route('admin.tests.create', ['course_id' => $course->id]))
            ->assertSee($activeItem->question)
            ->assertDontSee($disabledItem->question)
            ->assertSee('All 1 questions in this program are available');

        $this->from(route('admin.tests.create', ['course_id' => $course->id]))
            ->post(route('admin.tests.store-manual'), [
                'course_id' => $course->id,
                'title' => 'Exam with disabled question',
                'topic' => 'Topic',
                'available_from' => now()->addDay()->toDateTimeLocalString(),
                'time_limit' => 60,
                'question_count' => 1,
                'question_bank_item_ids' => [$disabledItem->id],
            ])
            ->assertSessionHasErrors([
                'question_bank_item_ids' => 'One or more selected questions are disabled and cannot be added to an examination.',
            ]);

        $this->assertDatabaseMissing('tests', ['title' => 'Exam with disabled question']);
    }

    public function test_faculty_can_submit_an_exam_for_any_program_in_their_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $anotherCourse = Course::create(['name' => 'Computer Science', 'code' => 'CS', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $item = QuestionBankItem::create([
            'course_id' => $anotherCourse->id,
            'author_id' => $faculty->id,
            'question' => 'Question in another program in my college?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);

        $this->actingAs($faculty)
            ->post(route('admin.tests.store-manual'), [
                'course_id' => $anotherCourse->id,
                'title' => 'College-wide program exam',
                'topic' => 'Topic',
                'available_from' => now()->addDay()->toDateTimeLocalString(),
                'time_limit' => 60,
                'question_count' => 1,
                'question_bank_item_ids' => [$item->id],
            ])
            ->assertRedirect(route('admin.tests.index'))
            ->assertSessionHas('success', 'Examination request submitted for Admin approval.');

        $this->assertDatabaseHas('tests', [
            'title' => 'College-wide program exam',
            'course_id' => $anotherCourse->id,
        ]);
    }

    public function test_faculty_cannot_submit_an_exam_for_a_program_outside_their_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $faculty->handledCourses()->attach($course);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Question outside the college?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);
        $exam = Exam::create([
            'title' => 'Outside college future exam',
            'topic' => 'Topic',
            'department_id' => $otherDepartment->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => Exam::APPROVAL_STATUS_PENDING,
            'available_from' => now()->addDay(),
        ]);

        $this->actingAs($faculty)
            ->post(route('admin.tests.store-manual'), [
                'course_id' => $course->id,
                'title' => 'Outside college exam',
                'topic' => 'Topic',
                'available_from' => now()->addDay()->toDateTimeLocalString(),
                'time_limit' => 60,
                'question_count' => 1,
                'question_bank_item_ids' => [$item->id],
            ])
            ->assertNotFound();

        $this->get(route('admin.tests.print', $exam))->assertNotFound();
        $this->get(route('admin.tests.edit', $exam))->assertNotFound();
        $this->assertDatabaseMissing('tests', ['title' => 'Outside college exam']);
    }

    public function test_faculty_exam_list_is_scoped_and_filtered_by_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $firstCourse = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $secondCourse = Course::create(['name' => 'Computer Science', 'code' => 'CS', 'department_id' => $department->id]);
        $thirdCourse = Course::create(['name' => 'Information Systems', 'code' => 'IS', 'department_id' => $department->id]);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $otherCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);

        foreach (range(1, 11) as $examNumber) {
            Exam::create([
                'title' => "Software Engineering exam {$examNumber}",
                'topic' => 'Topic',
                'department_id' => $department->id,
                'course_id' => $firstCourse->id,
                'created_by' => $faculty->id,
                'is_general' => false,
                'is_scheduled_exam' => true,
                'is_published' => false,
                'approval_status' => Exam::APPROVAL_STATUS_PENDING,
                'available_from' => now()->addHours($examNumber),
            ]);
        }
        Exam::create([
            'title' => 'Computer Science exam',
            'topic' => 'Topic',
            'department_id' => $department->id,
            'course_id' => $secondCourse->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => Exam::APPROVAL_STATUS_PENDING,
            'available_from' => now()->addDay(),
        ]);
        Exam::create([
            'title' => 'Information Systems exam',
            'topic' => 'Topic',
            'department_id' => $department->id,
            'course_id' => $thirdCourse->id,
            'created_by' => $admin->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'approval_status' => Exam::APPROVAL_STATUS_APPROVED,
            'available_from' => now()->addDay(),
        ]);
        Exam::create([
            'title' => 'Other college exam',
            'topic' => 'Topic',
            'department_id' => $otherDepartment->id,
            'course_id' => $otherCourse->id,
            'created_by' => $admin->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'approval_status' => Exam::APPROVAL_STATUS_APPROVED,
            'available_from' => now()->addDay(),
        ]);

        $this->actingAs($faculty)
            ->get(route('admin.tests.index'))
            ->assertSee('Software Engineering exam 1')
            ->assertSee('Computer Science exam')
            ->assertSee('Information Systems exam')
            ->assertDontSee('Other college exam')
            ->assertSee('All programs in your college')
            ->assertSee(route('admin.tests.index', ['view_all' => 1]), false);

        $this->get(route('admin.tests.index', ['course_id' => $firstCourse->id]))
            ->assertSee('Software Engineering exam 1')
            ->assertDontSee('Computer Science exam')
            ->assertDontSee('Information Systems exam')
            ->assertDontSee('Other college exam')
            ->assertSee(e(route('admin.tests.index', ['view_all' => 1, 'course_id' => $firstCourse->id])), false)
            ->assertViewHas('tests', fn ($tests): bool => $tests->total() === 11 && $tests->count() === 10);

        $this->get(route('admin.tests.index', ['course_id' => $thirdCourse->id]))
            ->assertSee('Information Systems exam')
            ->assertDontSee('Software Engineering exam 1')
            ->assertViewHas('tests', fn ($tests): bool => $tests->total() === 1);
    }

    public function test_faculty_submits_an_exam_using_another_facultys_question_as_a_pending_read_only_snapshot(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $questionOwner = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $topic = Topic::create([
            'course_id' => $course->id,
            'created_by' => $questionOwner->id,
            'name' => 'Shared questions',
        ]);
        $sharedItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'author_id' => $questionOwner->id,
            'question' => 'Can the Faculty reuse this question?',
            'options' => ['Yes', 'No', 'Maybe', 'Not sure'],
            'correct_answer' => 'Yes',
            'topic' => 'Shared questions',
            'image_path' => 'question-bank-images/shared-equation.png',
            'difficulty' => 'hard',
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
        $this->assertSame($topic->id, $examQuestion->topic_id);
        $this->assertSame($sharedItem->question, $examQuestion->question);
        $this->assertSame($sharedItem->options, $examQuestion->options);
        $this->assertSame($sharedItem->correct_answer, $examQuestion->correct_answer);
        $this->assertSame('Shared questions', $examQuestion->topic);
        $this->assertSame($sharedItem->image_path, $examQuestion->image_path);
        $this->assertSame('hard', $examQuestion->difficulty);
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
            ->assertSee('src="'.asset('storage/'.$sharedItem->image_path).'"', false)
            ->assertSee('notification-modal-trigger')
            ->assertSee('data-bs-target="#notificationModal"', false);

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
        $networkTopic = Topic::create([
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'name' => 'Network security',
        ]);
        $protocolTopic = Topic::create([
            'course_id' => $course->id,
            'created_by' => $otherFaculty->id,
            'name' => 'Web protocols',
        ]);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $networkTopic->id,
            'author_id' => $faculty->id,
            'question' => 'What protects web traffic?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'HTTP'],
            'correct_answer' => 'HTTPS',
            'topic' => 'Network security',
        ]);
        $otherItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $protocolTopic->id,
            'author_id' => $otherFaculty->id,
            'question' => 'Which protocol transfers web pages?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'IMAP'],
            'correct_answer' => 'HTTPS',
            'topic' => 'Network security',
        ]);
        $sameTopicItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $networkTopic->id,
            'author_id' => $faculty->id,
            'question' => 'Which layer secures a web connection?',
            'options' => ['Transport', 'Network', 'Application', 'Physical'],
            'correct_answer' => 'Transport',
            'topic' => 'Network security',
        ]);

        $this->actingAs($admin)
            ->post(route('super-admin.tests.store-manual'), [
                'course_id' => $course->id,
                'title' => 'Network Security Midterm',
                'description' => 'Answer every question.',
                'available_from' => '2026-03-20T11:00',
                'available_until' => '2026-03-20T13:00',
                'time_limit' => 60,
                'question_bank_item_ids' => [$item->id, $otherItem->id, $sameTopicItem->id],
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
            'topic' => 'Network security, Web protocols',
        ]);
        $this->assertSame('Scheduled', $exam->scheduleStatus());
        $this->assertSame(3, $exam->questions()->count());
        $questionIds = $exam->questions()->pluck('question_bank_item_id')->all();
        sort($questionIds);

        $this->assertSame(
            collect([$item->id, $otherItem->id, $sameTopicItem->id])->sort()->values()->all(),
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

    public function test_faculty_can_manually_select_questions_without_a_topic_or_auto_pick_count(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $topic = Topic::create([
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'name' => 'Algorithms',
        ]);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'author_id' => $faculty->id,
            'question' => 'Question from this program?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);

        $this->actingAs($faculty)
            ->post(route('admin.tests.store-manual'), [
                'course_id' => $course->id,
                'title' => 'Manual selection without auto-pick',
                'available_from' => '2026-03-22T10:00',
                'time_limit' => 60,
                'auto_pick_count' => 2,
                'question_bank_item_ids' => [$item->id],
            ])
            ->assertRedirect(route('admin.tests.index'))
            ->assertSessionHas('success', 'Examination request submitted for Admin approval.');

        $exam = Exam::query()->where('title', 'Manual selection without auto-pick')->firstOrFail();
        $this->assertSame('Algorithms', $exam->topic);
        $this->assertSame(1, $exam->questions()->count());
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
            ->assertSee('<h1 class="h2 fw-bold mb-2">Examinations</h1>', false)
            ->assertDontSee('Examinations &amp; Analysis')
            ->assertSee('exam-list__topic', false)
            ->assertSee('aria-label="View details for '.$exam->title.'"', false)
            ->assertSee('data-bs-target="#examDetailsModal'.$exam->id.'"', false)
            ->assertSee('bi-eye', false)
            ->assertDontSee('data-exam-details-target', false)
            ->assertSee('id="examDetailsModal'.$exam->id.'"', false)
            ->assertSee('Availability')
            ->assertSee('Questions')
            ->assertSee('Results')
            ->assertSee('Created by')
            ->assertSee('Time limit')
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
        $facultyNotification = $faculty->notifications()->firstOrFail();
        $this->assertSame(FacultyExamApprovalDecisionNotification::class, $facultyNotification->type);
        $this->assertSame('approved', $facultyNotification->data['decision']);
        $this->assertSame(route('admin.tests.index'), $facultyNotification->data['url']);

        $this->actingAs($faculty)
            ->get(route('admin.tests.index'))
            ->assertSee('Examination Approved')
            ->assertSee('Your examination "Pending examination" was approved by Admin.')
            ->assertSee('role-sidebar-navbar', false)
            ->assertSee('id="roleSidebarToggle"', false)
            ->assertSee('aria-label="Collapse sidebar"', false)
            ->assertSee('data-sidebar-label="Modules"', false)
            ->assertSee('bi bi-journal-bookmark', false)
            ->assertSee('bi bi-people', false)
            ->assertSee('--role-sidebar-collapsed-width: 4.75rem', false)
            ->assertSee('position: fixed !important;', false)
            ->assertSee('padding-top: calc(3.75rem + 2rem) !important;', false)
            ->assertSee("localStorage.setItem('lemu.role-sidebar-collapsed'", false)
            ->assertSee('Examinations')
            ->assertSee('Analysis')
            ->assertSee(route('admin.item-analysis.index'), false)
            ->assertSee('sidebar-account-links', false)
            ->assertSee(route('admin.profile'), false)
            ->assertSee('data-bs-target="#themeSettingsModal"', false)
            ->assertSee('Logout')
            ->assertSee('Signed in as '.$faculty->name)
            ->assertSee('notification-modal-trigger')
            ->assertSee('notification-dropdown__list')
            ->assertSee('data-bs-target="#notificationModal"', false);

        $this->actingAs($faculty)
            ->get(route('admin.notifications.index', [
                'notification' => $facultyNotification->id,
                'return_to' => '/admin/tests',
            ]))
            ->assertSee('Your examination "Pending examination" was approved by Admin.')
            ->assertSee('notifications-page__sidebar', false)
            ->assertSee('notifications-page__reader', false)
            ->assertSee('Close notification')
            ->assertSee('Delete');
        $this->assertNull($facultyNotification->fresh()->read_at);

        $this->actingAs($faculty)
            ->post(route('admin.notifications.read', $facultyNotification->id))
            ->assertRedirect();
        $this->assertNotNull($facultyNotification->fresh()->read_at);

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
        $facultyNotification = $faculty->notifications()->firstOrFail();
        $this->assertSame('rejected', $facultyNotification->data['decision']);
        $this->assertSame('Examination Rejected', $facultyNotification->data['title']);

        $otherFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $this->actingAs($otherFaculty)
            ->post(route('admin.notifications.read', $facultyNotification->id))
            ->assertNotFound();
        $this->assertNull($facultyNotification->fresh()->read_at);
    }

    public function test_admin_rejection_comment_is_saved_and_visible_to_the_exam_creator(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $exam = Exam::create([
            'title' => 'Examination needing revision',
            'topic' => 'Topic',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => Exam::APPROVAL_STATUS_PENDING,
        ]);
        $comment = 'Please correct the answer key before resubmitting.';

        $this->actingAs($admin)
            ->post(route('super-admin.tests.reject', $exam), ['comment' => $comment])
            ->assertRedirect();

        $this->assertDatabaseHas('tests', [
            'id' => $exam->id,
            'rejection_comment' => $comment,
        ]);
        $this->assertSame(
            $comment,
            $faculty->notifications()->firstOrFail()->data['comment'],
        );

        $this->actingAs($faculty)
            ->get(route('admin.tests.index'))
            ->assertOk()
            ->assertSee('Admin comment: '.$comment);
    }

    public function test_examination_schedule_shows_ten_items_and_view_all_reveals_the_remaining_items(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);

        foreach (range(1, 13) as $examNumber) {
            Exam::create([
                'title' => "Scheduled examination {$examNumber}",
                'topic' => 'Topic',
                'department_id' => $department->id,
                'course_id' => $course->id,
                'created_by' => $admin->id,
                'is_general' => false,
                'is_scheduled_exam' => true,
                'is_published' => true,
                'available_from' => now()->addHours($examNumber),
            ]);
        }

        $defaultList = $this->actingAs($admin)
            ->get(route('super-admin.tests.index'))
            ->assertSee('class="pagination"', false)
            ->assertDontSee('class="w-5 h-5"', false)
            ->assertSee('Showing 1–10 of 13 examinations.')
            ->assertSee('View all')
            ->assertViewHas('tests', fn ($tests): bool => $tests->count() === 10 && $tests->total() === 13);

        $this->get(route('super-admin.tests.index', ['view_all' => 1]))
            ->assertSee('Showing 1–13 of 13 examinations.')
            ->assertSee('Scheduled examination 1')
            ->assertSee('Scheduled examination 13')
            ->assertSee('Show first 10')
            ->assertViewHas('tests', fn ($tests): bool => $tests->count() === 13 && $tests->total() === 13);
    }

    public function test_exam_creator_can_edit_their_future_exam_and_faculty_changes_return_for_admin_review(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Updated question?',
            'options' => ['Right', 'Wrong one', 'Wrong two', 'Wrong three'],
            'correct_answer' => 'Right',
        ]);
        $exam = Exam::create([
            'title' => 'Original exam title',
            'topic' => 'Original topic',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'approval_status' => Exam::APPROVAL_STATUS_APPROVED,
            'reviewed_by' => $admin->id,
            'available_from' => now()->addHours(2),
            'available_until' => now()->addDay(),
            'time_limit' => 60,
        ]);
        Question::create([
            'test_id' => $exam->id,
            'question' => 'Original question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);

        $this->actingAs($faculty)
            ->get(route('admin.tests.edit', $exam))
            ->assertSee('Edit examination')
            ->assertSee('value="Original exam title"', false)
            ->assertSee('Updated question?');

        $this->put(route('admin.tests.update', $exam), [
            'title' => 'Updated exam title',
            'topic' => 'Updated topic',
            'description' => 'Updated instructions',
            'available_from' => '2026-03-20T13:00',
            'available_until' => '2026-03-20T15:00',
            'time_limit' => 45,
            'question_count' => 1,
            'question_bank_item_ids' => [$item->id],
        ])
            ->assertRedirect(route('admin.tests.index'))
            ->assertSessionHas('success', 'Examination updated and resubmitted for Admin approval.');

        $this->assertDatabaseHas('tests', [
            'id' => $exam->id,
            'title' => 'Updated exam title',
            'approval_status' => Exam::APPROVAL_STATUS_PENDING,
            'is_published' => false,
            'reviewed_by' => null,
        ]);
        $this->assertDatabaseHas('questions', [
            'test_id' => $exam->id,
            'question_bank_item_id' => $item->id,
            'question' => 'Updated question?',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
            'type' => FacultyExamApprovalRequestedNotification::class,
        ]);
    }

    public function test_only_the_creator_can_modify_future_exams_and_active_exams_cannot_be_modified(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $otherFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $futureExam = Exam::create([
            'title' => 'Another faculty future exam',
            'topic' => 'Topic',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $otherFaculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'available_from' => now()->addHour(),
        ]);
        $activeExam = Exam::create([
            'title' => 'Active examination',
            'topic' => 'Topic',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'available_from' => now()->subHour(),
        ]);
        $ownedFutureExam = Exam::create([
            'title' => 'My future examination',
            'topic' => 'Topic',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'available_from' => now()->addHours(2),
        ]);

        $this->actingAs($faculty)
            ->get(route('admin.tests.edit', $futureExam))
            ->assertNotFound();
        $this->delete(route('admin.tests.destroy', $futureExam))
            ->assertNotFound();
        $this->get(route('admin.tests.edit', $activeExam))
            ->assertNotFound();
        $this->delete(route('admin.tests.destroy', $activeExam))
            ->assertNotFound();

        $this->get(route('admin.tests.index'))
            ->assertSee('href="'.route('admin.tests.edit', $ownedFutureExam).'"', false)
            ->assertSee('id="examDetailsModal'.$ownedFutureExam->id.'"', false)
            ->assertSee('exam-list__delete-form', false)
            ->assertSee('action="'.route('admin.tests.destroy', $ownedFutureExam).'"', false)
            ->assertSee('data-delete-exam="'.$ownedFutureExam->id.'"', false)
            ->assertSee('data-delete-unavailable="'.$activeExam->id.'"', false)
            ->assertSee('This examination can only be deleted before it opens.')
            ->assertDontSee('href="'.route('admin.tests.edit', $activeExam).'"', false)
            ->assertDontSee('action="'.route('admin.tests.destroy', $activeExam).'"', false)
            ->assertDontSee('data-delete-exam="'.$activeExam->id.'"', false)
            ->assertDontSee('href="'.route('admin.tests.edit', $futureExam).'"', false);

        $this->assertDatabaseHas('tests', ['id' => $futureExam->id]);
        $this->assertDatabaseHas('tests', ['id' => $activeExam->id]);
    }

    public function test_exam_creator_can_delete_their_future_exam(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $exam = Exam::create([
            'title' => 'Future exam to delete',
            'topic' => 'Topic',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => Exam::APPROVAL_STATUS_PENDING,
            'available_from' => now()->addHour(),
        ]);

        $this->actingAs($faculty)
            ->delete(route('admin.tests.destroy', $exam))
            ->assertRedirect(route('admin.tests.index'))
            ->assertSessionHas('success', 'Scheduled examination deleted.');

        $this->assertDatabaseMissing('tests', ['id' => $exam->id]);
    }

    public function test_printable_exam_paper_shows_lemu_college_program_student_fields_and_options_only(): void
    {
        $department = Department::create(['name' => 'College of Engineering', 'code' => 'COE']);
        $course = Course::create(['name' => 'Civil Engineering', 'code' => 'CE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Which structure carries the load?',
            'options' => ['Beam', 'Window', 'Door', 'Tile'],
            'correct_answer' => 'Beam',
            'explanation' => 'This explanation must remain private.',
            'image_path' => 'question-bank-images/beam-diagram.png',
            'difficulty' => 'moderate',
        ]);
        $exam = Exam::create([
            'title' => 'Structural Engineering Midterm',
            'topic' => 'Structural analysis',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => Exam::APPROVAL_STATUS_PENDING,
            'available_from' => now()->addHour(),
        ]);
        Question::create([
            'test_id' => $exam->id,
            'question_bank_item_id' => $item->id,
            'question' => $item->question,
            'options' => $item->options,
            'correct_answer' => $item->correct_answer,
            'explanation' => $item->explanation,
            'image_path' => $item->image_path,
            'difficulty' => $item->difficulty,
        ]);

        $this->actingAs($faculty)
            ->get(route('admin.tests.print', $exam))
            ->assertSee('LEMU')
            ->assertSee('College of Engineering')
            ->assertSee('Civil Engineering')
            ->assertSee('Name:')
            ->assertSee('Date:')
            ->assertSee('Encircle the letter of the correct answer.')
            ->assertSee('Which structure carries the load?')
            ->assertSee('src="'.asset('storage/'.$item->image_path).'"', false)
            ->assertSee('Beam')
            ->assertSee('src="'.asset('storage/images/systemlogo.jpg').'"', false)
            ->assertDontSee('This explanation must remain private.')
            ->assertDontSee('Correct answer');

        $otherFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $this->actingAs($otherFaculty)
            ->get(route('admin.tests.print', $exam))
            ->assertNotFound();
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
            ->assertSee('Examinations')
            ->assertSee('Analysis')
            ->assertSee('href="'.route('super-admin.item-analysis.index').'"', false)
            ->assertSee('Analyze')
            ->assertSee('href="'.e($analysisUrl).'"', false)
            ->assertSee('href="'.route('super-admin.tests.index').'"', false);
    }
}
