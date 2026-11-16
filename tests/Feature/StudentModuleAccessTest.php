<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Module;
use App\Models\Subtopic;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentModuleAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_only_sees_modules_from_their_department(): void
    {
        $engineering = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $education = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $engineering->id]);
        $engineeringModule = Module::create(['title' => 'Circuits', 'department_id' => $engineering->id]);
        $educationModule = Module::create(['title' => 'Teaching Methods', 'department_id' => $education->id]);

        $response = $this->actingAs($student)->get(route('student.learn.index'));

        $response->assertSee($engineeringModule->title);
        $response->assertDontSee($educationModule->title);
    }

    public function test_student_cannot_open_a_module_from_another_department(): void
    {
        $engineering = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $education = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $engineering->id]);
        $educationModule = Module::create(['title' => 'Teaching Methods', 'department_id' => $education->id]);

        $this->actingAs($student)
            ->get(route('student.learn.show', $educationModule))
            ->assertNotFound();
    }

    public function test_student_cannot_review_material_from_another_department(): void
    {
        $engineering = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $education = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $engineering->id]);
        $educationModule = Module::create(['title' => 'Teaching Methods', 'department_id' => $education->id]);
        $subtopic = Subtopic::create(['module_id' => $educationModule->id, 'title' => 'Lesson Planning']);

        $this->actingAs($student)
            ->get(route('student.learn.review', $subtopic))
            ->assertNotFound();
    }

    public function test_student_cannot_generate_a_quiz_from_another_departments_material(): void
    {
        $engineering = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $education = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $engineering->id]);
        $educationModule = Module::create(['title' => 'Teaching Methods', 'department_id' => $education->id]);
        $subtopic = Subtopic::create(['module_id' => $educationModule->id, 'title' => 'Lesson Planning']);

        $this->actingAs($student)
            ->get(route('student.learn.quiz', $subtopic))
            ->assertNotFound();
    }

    public function test_student_is_notified_when_no_modules_exist_for_a_general_test(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);

        $this->actingAs($student)
            ->get(route('student.tests.generate-general'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
