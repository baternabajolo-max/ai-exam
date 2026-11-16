<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\QuestionBankItem;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuestionBankControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_faculty_question_bank_opens_create_and_edit_forms_from_modal_buttons(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'What protects web traffic?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'HTTP'],
            'correct_answer' => 'HTTPS',
            'topic' => 'Network security',
        ]);

        $this->actingAs($faculty)
            ->get(route('admin.question-bank.index'))
            ->assertSee('data-bs-target="#createQuestionModal"', false)
            ->assertSee('Create a question')
            ->assertSee('id="createQuestionModal"', false)
            ->assertSee('Create question')
            ->assertSee('question-bank-modal')
            ->assertSee('modal-footer px-4 py-3', false)
            ->assertSee('data-bs-target="#viewQuestionModal'.$item->id.'"', false)
            ->assertSee('id="viewQuestionModal'.$item->id.'"', false)
            ->assertSee('aria-label="View question and answer choices"', false)
            ->assertSee('HTTPS')
            ->assertSee('FTP')
            ->assertSee('Correct answer')
            ->assertSee('id="editQuestionModal'.$item->id.'"', false)
            ->assertSee('data-open-edit-modal="#editQuestionModal'.$item->id.'"', false)
            ->assertSee(route('admin.question-bank.destroy', $item), false)
            ->assertSee('name="form_context" value="create"', false)
            ->assertSee('name="form_context" value="edit-'.$item->id.'"', false)
            ->assertDontSee('View Item Analysis');
    }

    public function test_faculty_can_create_a_question_for_a_program_in_their_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);

        $this->actingAs($faculty)
            ->post(route('admin.question-bank.store'), [
                'course_id' => $course->id,
                'question' => 'Which protocol secures web traffic?',
                'options' => ['HTTP', 'FTP', 'HTTPS', 'SMTP'],
                'correct_option' => 2,
                'topic' => 'Network security',
                'explanation' => 'HTTPS uses TLS to protect traffic.',
            ])
            ->assertRedirect();

        $item = QuestionBankItem::query()->firstOrFail();
        $this->assertDatabaseHas('question_bank_items', [
            'id' => $item->id,
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'correct_answer' => 'HTTPS',
        ]);
        $this->assertSame(['HTTP', 'FTP', 'HTTPS', 'SMTP'], $item->options);
    }

    public function test_faculty_cannot_update_or_delete_a_question_from_another_program(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $otherCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $otherFaculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $otherDepartment->id,
            'course_id' => $otherCourse->id,
        ]);
        $item = QuestionBankItem::create([
            'course_id' => $otherCourse->id,
            'author_id' => $otherFaculty->id,
            'question' => 'Foreign program question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'topic' => 'Teaching',
        ]);

        $this->actingAs($faculty)
            ->put(route('admin.question-bank.update', $item), [
                'question' => 'Changed?',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option' => 0,
                'topic' => 'Changed',
            ])
            ->assertNotFound();

        $this->delete(route('admin.question-bank.destroy', $item))->assertNotFound();
        $this->assertDatabaseHas('question_bank_items', [
            'id' => $item->id,
            'question' => 'Foreign program question?',
        ]);
    }

    public function test_faculty_cannot_edit_or_delete_another_facultys_question_in_the_same_program(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $questionOwner = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $questionOwner->id,
            'question' => 'Question owned by another Faculty member?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);

        $this->actingAs($faculty)
            ->put(route('admin.question-bank.update', $item), [
                'question' => 'Changed by another Faculty member?',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option' => 0,
                'topic' => 'Changed',
            ])
            ->assertNotFound();

        $this->delete(route('admin.question-bank.destroy', $item))->assertNotFound();
        $this->assertDatabaseHas('question_bank_items', [
            'id' => $item->id,
            'author_id' => $questionOwner->id,
            'question' => 'Question owned by another Faculty member?',
        ]);
    }

    public function test_admin_can_inspect_questions_without_editing_them(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'What does TLS protect?',
            'options' => ['Confidentiality', 'Email routing', 'File compression', 'Power supply'],
            'correct_answer' => 'Confidentiality',
            'topic' => 'Network security',
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.question-bank.index'))
            ->assertOk()
            ->assertSee($item->question)
            ->assertSee('data-bs-target="#viewQuestionModal'.$item->id.'"', false)
            ->assertSee('id="viewQuestionModal'.$item->id.'"', false)
            ->assertSee('aria-label="View question and answer choices"', false)
            ->assertSee('Confidentiality')
            ->assertSee('Email routing')
            ->assertSee('Correct answer')
            ->assertDontSee('Network security')
            ->assertSee('Select a Faculty member to see their questions across all programs')
            ->assertSee('data-bs-target="#filterQuestionBankModal"', false)
            ->assertSee('id="filterQuestionBankModal"', false)
            ->assertSee('name="author_id"', false)
            ->assertSee('Apply filters')
            ->assertSee('data-clear-question-bank-filters', false)
            ->assertSee('Results update only after you apply the filters.')
            ->assertDontSee('facultySelect.form.requestSubmit()', false)
            ->assertDontSee('programSelect.form.requestSubmit()', false)
            ->assertDontSee('name="department_id"', false)
            ->assertDontSee('name="course_id"', false)
            ->assertDontSee('id="question-bank-department"', false)
            ->assertDontSee(route('admin.question-bank.update', $item));

        $this->actingAs(User::factory()->create(['role' => 'student']))
            ->get(route('admin.question-bank.index'))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_admin_can_filter_the_question_bank_to_one_faculty_member(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $firstFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $secondFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $firstItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $firstFaculty->id,
            'question' => 'First Faculty question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);
        QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $secondFaculty->id,
            'question' => 'Second Faculty question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'B',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.question-bank.index', ['author_id' => $firstFaculty->id]))
            ->assertSee($firstItem->question)
            ->assertDontSee('Second Faculty question?')
            ->assertSee($firstFaculty->name);
    }

    public function test_selecting_a_faculty_shows_all_their_program_questions_and_allows_optional_program_filtering(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $secondCourse = Course::create(['name' => 'Information Systems', 'code' => 'IS', 'department_id' => $department->id]);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $otherCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $otherFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => 'Question in first program?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);
        QuestionBankItem::create([
            'course_id' => $secondCourse->id,
            'author_id' => $faculty->id,
            'question' => 'Question in second program?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);
        QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $otherFaculty->id,
            'question' => 'Another Faculty question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.question-bank.index', ['author_id' => $faculty->id]))
            ->assertViewHas('selectedCourseId', null)
            ->assertDontSee('name="department_id"', false)
            ->assertDontSee('id="question-bank-department"', false)
            ->assertDontSee('id="question-bank-college-name"', false)
            ->assertSee('College: '.$department->name)
            ->assertSee('id="question-bank-author"', false)
            ->assertSee('value="'.$faculty->id.'" selected', false)
            ->assertSee('name="course_id" class="form-select form-select-lg"', false)
            ->assertSee('All programs')
            ->assertSee('Question in first program?')
            ->assertSee('Question in second program?')
            ->assertDontSee('Another Faculty question?')
            ->assertSee($course->name)
            ->assertSee($secondCourse->name)
            ->assertDontSee($otherCourse->name);

        $this->get(route('super-admin.question-bank.index', [
            'author_id' => $faculty->id,
            'course_id' => $course->id,
        ]))
            ->assertSee('Question in first program?')
            ->assertDontSee('Question in second program?');

        $this->get(route('super-admin.question-bank.index', [
            'author_id' => $faculty->id,
            'course_id' => $otherCourse->id,
        ]))->assertNotFound();

        $this->get(route('super-admin.question-bank.index', ['course_id' => $course->id]))
            ->assertNotFound();
    }
}
