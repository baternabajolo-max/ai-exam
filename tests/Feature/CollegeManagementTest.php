<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CollegeManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_super_admin_college_page_displays_college_labels_and_course_edit_form(): void
    {
        $college = Department::create(['name' => 'College of Computing', 'code' => 'COC']);
        $course = Course::create([
            'name' => 'Information Technology',
            'code' => 'BSIT',
            'department_id' => $college->id,
        ]);
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.departments.index'))
            ->assertSee('Colleges & courses')
            ->assertSee('Create college')
            ->assertSee('Manage college')
            ->assertSee('College of Computing')
            ->assertSee('Information Technology')
            ->assertSee(route('super-admin.courses.update', $course), false)
            ->assertDontSee('college-code')
            ->assertDontSee('name="code"', false)
            ->assertDontSee('BSIT')
            ->assertDontSee('Edit course names or codes')
            ->assertSee('sweetalert2@11.26.3/dist/sweetalert2.min.css')
            ->assertSee('sweetalert2@11.26.3/dist/sweetalert2.all.min.js')
            ->assertSee('js/confirm-actions.js')
            ->assertSee('data-confirm-title="Delete this course?"', false)
            ->assertSee('data-confirm-button="Delete course"', false)
            ->assertSee('data-confirm-title="Delete this college?"', false)
            ->assertSee('data-confirm-button="Delete college"', false)
            ->assertSee('id="collegeModal'.$college->id.'"', false);
    }

    public function test_super_admin_can_create_multiple_colleges_with_generated_unique_codes(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($superAdmin)
            ->from(route('super-admin.departments.index'))
            ->post(route('super-admin.departments.store'), ['name' => 'College of Computing'])
            ->assertRedirect(route('super-admin.departments.index'))
            ->assertSessionHas('success', 'College created successfully.');

        $this->from(route('super-admin.departments.index'))
            ->post(route('super-admin.departments.store'), ['name' => 'College of Arts'])
            ->assertRedirect(route('super-admin.departments.index'))
            ->assertSessionHas('success', 'College created successfully.');

        $this->assertDatabaseHas('departments', [
            'name' => 'College of Computing',
            'code' => 'COL',
        ]);
        $this->assertDatabaseHas('departments', [
            'name' => 'College of Arts',
            'code' => 'COL1',
        ]);
    }

    public function test_admin_faculty_page_assigns_faculty_to_colleges(): void
    {
        $college = Department::create(['name' => 'College of Computing', 'code' => 'COC']);
        $faculty = User::factory()->create([
            'name' => 'Faculty Member',
            'role' => 'admin',
            'department_id' => $college->id,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.users.index'))
            ->assertSee('sweetalert2@11.26.3/dist/sweetalert2.min.css')
            ->assertSee('sweetalert2@11.26.3/dist/sweetalert2.all.min.js')
            ->assertSee('js/confirm-actions.js')
            ->assertSee('College faculty')
            ->assertSee('Faculty Accounts')
            ->assertSee('faculty accounts assigned to colleges')
            ->assertSee('Faculty member')
            ->assertSee('Faculty')
            ->assertSee('College')
            ->assertSee('College of Computing')
            ->assertSee('class="delete-form delete-faculty"', false)
            ->assertSee('data-confirm-title="Delete this faculty account?"', false)
            ->assertSee('data-confirm-button="Delete faculty"', false)
            ->assertDontSee('onsubmit="return confirm(', false);

        $this->assertDatabaseHas('users', [
            'id' => $faculty->id,
            'role' => 'admin',
        ]);
    }

    public function test_super_admin_results_route_uses_college_terminology(): void
    {
        Department::create(['name' => 'College of Computing', 'code' => 'COC']);

        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $this->actingAs($superAdmin)
            ->get(route('super-admin.leaderboard'))
            ->assertRedirect(route('super-admin.results.index'));

        $this->get(route('super-admin.results.index'))
            ->assertSee('Results')
            ->assertSee('College')
            ->assertSee('College of Computing');
    }

    public function test_super_admin_can_update_course_name_without_changing_internal_code(): void
    {
        $college = Department::create(['name' => 'College of Computing', 'code' => 'COC']);
        $course = Course::create([
            'name' => 'Information Technology',
            'code' => 'BSIT',
            'department_id' => $college->id,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->from(route('super-admin.departments.index'))
            ->put(route('super-admin.courses.update', $course), [
                'name' => 'Computer Science',
            ])
            ->assertRedirect(route('super-admin.departments.index'))
            ->assertSessionHas('success', 'Course updated successfully.');

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Computer Science',
            'code' => 'BSIT',
            'department_id' => $college->id,
        ]);
    }

    public function test_duplicate_college_name_is_rejected_without_changing_college(): void
    {
        $college = Department::create(['name' => 'College of Computing', 'code' => 'COC']);
        Department::create(['name' => 'College of Arts', 'code' => 'COA']);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->from(route('super-admin.departments.index'))
            ->put(route('super-admin.departments.update', $college), [
                'name' => 'College of Arts',
            ])
            ->assertRedirect(route('super-admin.departments.index'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('departments', [
            'id' => $college->id,
            'name' => 'College of Computing',
        ]);
    }

    public function test_course_with_assigned_modules_cannot_be_deleted(): void
    {
        $college = Department::create(['name' => 'College of Computing', 'code' => 'COC']);
        $course = Course::create([
            'name' => 'Information Technology',
            'code' => 'BSIT',
            'department_id' => $college->id,
        ]);
        $module = Module::create([
            'title' => 'Networking',
            'department_id' => $college->id,
            'course_id' => $course->id,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->from(route('super-admin.departments.index'))
            ->delete(route('super-admin.courses.destroy', $course))
            ->assertRedirect(route('super-admin.departments.index'))
            ->assertSessionHasErrors('course');

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        $this->assertDatabaseHas('modules', ['id' => $module->id, 'course_id' => $course->id]);
    }

    public function test_college_admin_cannot_update_course(): void
    {
        $college = Department::create(['name' => 'College of Computing', 'code' => 'COC']);
        $course = Course::create([
            'name' => 'Information Technology',
            'code' => 'BSIT',
            'department_id' => $college->id,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put(route('super-admin.courses.update', $course), [
                'name' => 'Unauthorized update',
                'code' => 'NOPE',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Information Technology',
            'code' => 'BSIT',
        ]);
    }
}
