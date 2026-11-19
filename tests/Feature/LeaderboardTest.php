<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Module;
use App\Models\Result;
use App\Models\Subtopic;
use App\Models\Test as StudentTest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_all_role_leaderboards_use_the_students_accessible_course_points(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $softwareCourse = Course::create([
            'name' => 'Software Engineering',
            'code' => 'SE',
            'department_id' => $department->id,
        ]);
        $systemsCourse = Course::create([
            'name' => 'Information Systems',
            'code' => 'IS',
            'department_id' => $department->id,
        ]);
        $student = User::factory()->create([
            'name' => 'Software Student',
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $softwareCourse->id,
            'total_points' => 99,
        ]);
        $systemsStudent = User::factory()->create([
            'name' => 'Systems Student',
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $systemsCourse->id,
            'total_points' => 0,
        ]);
        $foreignDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        User::factory()->create([
            'name' => 'Foreign Student',
            'role' => 'student',
            'department_id' => $foreignDepartment->id,
            'total_points' => 90,
        ]);

        $this->createCourseQuiz($department, $softwareCourse, 'Software Design', 'Algorithms', $student, 2);
        $systemsTest = $this->createCourseQuiz(
            $department,
            $systemsCourse,
            'Systems Analysis',
            'Databases',
            $student,
            4,
        );
        Result::create([
            'user_id' => $systemsStudent->id,
            'test_id' => $systemsTest->id,
            'score' => 4,
            'total_questions' => 4,
            'answers' => ['a', 'b', 'c', 'd'],
        ]);

        $this->actingAs($student)
            ->get(route('student.leaderboard'))
            ->assertSee('Software Student')
            ->assertSee('Systems Student')
            ->assertSee('15 pts')
            ->assertDontSee('Foreign Student');

        $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
        ]))
            ->get(route('admin.leaderboard'))
            ->assertRedirect(route('faculty.results.index'));

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.leaderboard', ['department_id' => $department->id]))
            ->assertRedirect(route('super-admin.results.index', ['college_id' => $department->id]));

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'total_points' => 15,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $systemsStudent->id,
            'total_points' => 30,
        ]);
    }

    public function test_leaderboard_routes_redirect_users_with_another_role_to_their_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($student)
            ->get(route('admin.leaderboard'))
            ->assertRedirect(route('student.dashboard'));

        $this->actingAs($admin)
            ->get(route('super-admin.leaderboard'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($superAdmin)
            ->get(route('student.leaderboard'))
            ->assertRedirect(route('super-admin.dashboard'));
    }

    public function test_student_leaderboard_remains_scoped_and_faculty_legacy_route_redirects_to_results(): void
    {
        $unassignedStudent = User::factory()->create([
            'name' => 'Unassigned Viewer',
            'role' => 'student',
            'department_id' => null,
        ]);
        User::factory()->create([
            'name' => 'Unassigned Student',
            'role' => 'student',
            'department_id' => null,
            'total_points' => 45,
        ]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => null,
        ]);

        $this->actingAs($unassignedStudent)
            ->get(route('student.leaderboard'))
            ->assertDontSee('Unassigned Student')
            ->assertSee('No Points Awarded Yet');

        $this->actingAs($faculty)
            ->get(route('admin.leaderboard'))
            ->assertRedirect(route('faculty.results.index'));
    }

    public function test_super_admin_leaderboard_redirects_to_results_with_the_college_filter(): void
    {
        $college = Department::create(['name' => 'College A', 'code' => 'COA']);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.leaderboard', ['department_id' => $college->id]))
            ->assertRedirect(route('super-admin.results.index', ['college_id' => $college->id]));
    }

    private function createCourseQuiz(
        Department $department,
        Course $course,
        string $moduleTitle,
        string $subtopicTitle,
        User $student,
        int $score,
    ): StudentTest {
        $module = Module::create([
            'title' => $moduleTitle,
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $subtopic = Subtopic::create([
            'module_id' => $module->id,
            'title' => $subtopicTitle,
        ]);
        $test = StudentTest::create([
            'title' => $subtopicTitle.' Review Quiz',
            'topic' => $subtopicTitle,
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => false,
        ]);

        Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => $score,
            'total_questions' => 4,
            'answers' => ['a', 'b', 'c', 'd'],
        ]);

        return $test;
    }
}
