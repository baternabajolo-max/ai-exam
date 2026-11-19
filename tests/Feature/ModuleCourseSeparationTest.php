<?php

namespace Tests\Feature;

use App\Jobs\ProcessMathPdfJob;
use App\Models\Course;
use App\Models\Department;
use App\Models\Module;
use App\Models\Result;
use App\Models\StudentNotification;
use App\Models\StudySession;
use App\Models\Subtopic;
use App\Models\Test as StudentTest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ModuleCourseSeparationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_faculty_can_see_and_manage_modules_across_their_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $otherCourse = Course::create(['name' => 'Information Systems', 'code' => 'IS', 'department_id' => $department->id]);
        $foreignDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $foreignCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $foreignDepartment->id]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.modules.index'))
            ->assertSee('Modules')
            ->assertSee('Choose Program')
            ->assertSee($otherCourse->name)
            ->assertSee('Add New Module')
            ->assertDontSee('Courses');

        $this->post(route('admin.modules.store'), ['title' => 'Software Foundations', 'course_id' => $course->id])
            ->assertRedirect(route('admin.modules.index', ['course_id' => $course->id]));
        $this->assertDatabaseHas('modules', [
            'title' => 'Software Foundations',
            'course_id' => $course->id,
        ]);

        $this->post(route('admin.modules.store'), ['title' => 'Systems Analysis', 'course_id' => $otherCourse->id])
            ->assertRedirect(route('admin.modules.index', ['course_id' => $otherCourse->id]));
        $this->assertDatabaseHas('modules', [
            'title' => 'Systems Analysis',
            'course_id' => $otherCourse->id,
        ]);

        $this->get(route('admin.modules.index'))
            ->assertSee('Modules')
            ->assertSee('Choose Program')
            ->assertSee('Add New Module')
            ->assertSee('id="createModuleModal"', false)
            ->assertSee('Software Foundations')
            ->assertSee('Systems Analysis');

        $this->post(route('admin.modules.store'), ['title' => 'Foreign Module', 'course_id' => $foreignCourse->id])
            ->assertNotFound();
        $this->assertDatabaseMissing('modules', ['title' => 'Foreign Module']);
    }

    public function test_admin_can_create_and_filter_modules_by_course(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $softwareEngineering = Course::create([
            'name' => 'Software Engineering',
            'code' => 'SE',
            'department_id' => $department->id,
        ]);
        $informationSystems = Course::create([
            'name' => 'Information Systems',
            'code' => 'IS',
            'department_id' => $department->id,
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);

        $response = $this->actingAs($admin)->post(route('admin.modules.store'), [
            'title' => 'Software Design',
            'description' => 'Design patterns and architecture',
            'course_id' => $softwareEngineering->id,
        ]);

        $response->assertRedirect(route('admin.modules.index', ['course_id' => $softwareEngineering->id]));
        $this->assertDatabaseHas('modules', [
            'title' => 'Software Design',
            'department_id' => $department->id,
            'course_id' => $softwareEngineering->id,
        ]);

        $otherModule = Module::create([
            'title' => 'Systems Analysis',
            'department_id' => $department->id,
            'course_id' => $informationSystems->id,
        ]);

        $this->get(route('admin.modules.index', ['course_id' => $softwareEngineering->id]))
            ->assertSee('Software Design')
            ->assertDontSee($otherModule->title)
            ->assertSee('Software Engineering')
            ->assertDontSee('Choose Program');
    }

    public function test_module_management_modals_are_outside_transformed_containers(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $module = Module::create([
            'title' => 'Software Design',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Architecture']);

        $this->actingAs($admin);

        $modulesPage = $this->get(route('admin.modules.index', ['course_id' => $course->id]))
            ->assertSee('data-bs-target="#deleteModuleModal'.$module->id.'"', false)
            ->assertSee('id="deleteModuleModal'.$module->id.'"', false)
            ->assertSee(route('admin.modules.destroy', $module), false);
        $this->assertModalIsOutsideTransformedContainers($modulesPage, 'deleteModuleModal'.$module->id);

        $modulePage = $this->get(route('admin.modules.show', $module))
            ->assertSee('data-bs-target="#editSubtopicModal'.$subtopic->id.'"', false)
            ->assertSee('id="editSubtopicModal'.$subtopic->id.'"', false)
            ->assertSee('data-reviewer-upload', false)
            ->assertSee('id="reviewer-upload-progress"', false)
            ->assertSee('Uploading topic material')
            ->assertSee('Please wait while your reviewer file is uploaded.')
            ->assertSee('data-confirm-title="Delete this topic?"', false)
            ->assertSee('data-confirm-button="Delete topic"', false)
            ->assertSee('Back to Modules')
            ->assertSee('aria-label="Module details"', false)
            ->assertSee('No description provided for this module.')
            ->assertSee('Leaderboards')
            ->assertSee('Student Study Activity')
            ->assertSee('Search student')
            ->assertSee('Filter by Topic')
            ->assertSee('Topics Completed')
            ->assertSee('Counts active reading time after at least one quiz attempt', false)
            ->assertSee('Completed requires at least 5 minutes of active study', false)
            ->assertSee('Shows the average across quiz attempts', false)
            ->assertSee('Most recent review session or quiz attempt.', false)
            ->assertSee('${data.topics_completed} / ${data.topic_count} Topics', false)
            ->assertSee('data-bs-container="body"', false)
            ->assertSee('moduleTopicBreakdownRows', false)
            ->assertSee('moduleTopicAttemptRows', false)
            ->assertSee('Topics Breakdown')
            ->assertSee('id="moduleActivityDrilldownTabs"', false)
            ->assertSee('data-activity-stage="days" disabled', false)
            ->assertSee('data-activity-stage="topics" disabled', false)
            ->assertSee('Minutes (min)')
            ->assertSee('Hours (h)')
            ->assertSee('function selectActivityMonth', false)
            ->assertSee('function selectActivityDate', false)
            ->assertSee('js/confirm-actions.js')
            ->assertDontSee('if (!confirm(', false)
            ->assertDontSee('data-ai-loading-title="Saving reviewer"', false)
            ->assertDontSee('data-ai-loading-title="Uploading reviewer"', false)
            ->assertSee(route('admin.subtopics.update', $subtopic), false);
        $this->assertModalIsOutsideTransformedContainers($modulePage, 'editSubtopicModal'.$subtopic->id);
        $this->assertModalIsOutsideTransformedContainers($modulePage, 'moduleStudentActivityModal');
        $this->assertModalIsOutsideTransformedContainers($modulePage, 'moduleStudentNoticeModal');
    }

    public function test_module_detail_ranks_all_students_by_quiz_points_and_verified_review_time(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $quizStudent = User::factory()->create([
            'name' => 'Quiz Student',
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $zeroPointStudent = User::factory()->create([
            'name' => 'Zero Point Student',
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $module = Module::create(['title' => 'Database Systems', 'department_id' => $department->id, 'course_id' => $course->id]);
        $verifiedTopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Normalization']);
        $unverifiedTopic = Subtopic::create(['module_id' => $module->id, 'title' => 'SQL Queries']);
        $quiz = StudentTest::create([
            'title' => 'Normalization Quiz',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'subtopic_id' => $verifiedTopic->id,
            'is_general' => false,
        ]);
        Result::create([
            'user_id' => $quizStudent->id,
            'test_id' => $quiz->id,
            'score' => 8,
            'total_questions' => 10,
        ]);
        Result::create([
            'user_id' => $quizStudent->id,
            'test_id' => $quiz->id,
            'score' => 10,
            'total_questions' => 10,
        ]);

        StudySession::create([
            'user_id' => $quizStudent->id,
            'module_id' => $module->id,
            'subtopic_id' => $verifiedTopic->id,
            'activity_date' => now()->toDateString(),
            'active_seconds' => 3600,
            'started_at' => now()->subHour(),
            'last_heartbeat_at' => now(),
        ]);
        StudySession::create([
            'user_id' => $quizStudent->id,
            'module_id' => $module->id,
            'subtopic_id' => $unverifiedTopic->id,
            'activity_date' => now()->toDateString(),
            'active_seconds' => 1800,
            'started_at' => now()->subHour(),
            'last_heartbeat_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.modules.show', $module))
            ->assertOk()
            ->assertSee('90.00 pts')
            ->assertSee('90.00%')
            ->assertSee('Quiz Student')
            ->assertSee('Zero Point Student')
            ->assertSee('30m')
            ->assertSee('No attempts')
            ->assertSee('data-notice-preset="Great study progress!"', false);
    }

    public function test_module_detail_embeds_topic_specific_points_and_verified_time_for_filters(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $module = Module::create(['title' => 'Database Systems', 'department_id' => $department->id, 'course_id' => $course->id]);
        $normalization = Subtopic::create(['module_id' => $module->id, 'title' => 'Normalization']);
        $sqlQueries = Subtopic::create(['module_id' => $module->id, 'title' => 'SQL Queries']);

        foreach ([
            [$normalization, 8, 600],
            [$sqlQueries, 4, 900],
        ] as [$topic, $score, $activeSeconds]) {
            $quiz = StudentTest::create([
                'title' => "{$topic->title} Quiz",
                'department_id' => $department->id,
                'course_id' => $course->id,
                'subtopic_id' => $topic->id,
                'is_general' => false,
            ]);
            Result::create([
                'user_id' => $student->id,
                'test_id' => $quiz->id,
                'score' => $score,
                'total_questions' => 10,
            ]);
            StudySession::create([
                'user_id' => $student->id,
                'module_id' => $module->id,
                'subtopic_id' => $topic->id,
                'activity_date' => now()->toDateString(),
                'active_seconds' => $activeSeconds,
                'started_at' => now()->subMinutes($activeSeconds / 60),
                'last_heartbeat_at' => now(),
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.modules.show', $module));
        $response
            ->assertSee('Filter by Topic')
            ->assertSee('All Topics')
            ->assertSee($normalization->title)
            ->assertSee($sqlQueries->title);

        preg_match('/data-topic-stats="([^"]+)"/', $response->getContent(), $matches);
        $topicStats = json_decode(
            html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(40, $topicStats[$normalization->id]['points']);
        $this->assertSame(600, $topicStats[$normalization->id]['verified_seconds']);
        $this->assertSame(20, $topicStats[$sqlQueries->id]['points']);
        $this->assertSame(900, $topicStats[$sqlQueries->id]['verified_seconds']);
    }

    public function test_faculty_can_send_a_one_way_module_notice_only_to_an_enrolled_student(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $otherCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $foreignStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $otherDepartment->id,
            'course_id' => $otherCourse->id,
        ]);
        $module = Module::create(['title' => 'Database Systems', 'department_id' => $department->id, 'course_id' => $course->id]);

        $this->actingAs($admin)
            ->post(route('admin.modules.students.notifications.store', [
                'module' => $module,
                'student' => $student,
            ]), ['message' => 'Great study progress!'])
            ->assertRedirect();

        $this->assertDatabaseHas('student_notifications', [
            'student_id' => $student->id,
            'admin_id' => $admin->id,
            'subject' => $module->title,
            'message' => 'Great study progress!',
            'read_at' => null,
        ]);

        $this->post(route('admin.modules.students.notifications.store', [
            'module' => $module,
            'student' => $foreignStudent,
        ]), ['message' => 'Unauthorized notice'])
            ->assertNotFound();

        $this->assertSame(1, StudentNotification::count());
    }

    public function test_admin_can_upload_reviewer_files_up_to_75_mb_and_larger_files_are_rejected(): void
    {
        Queue::fake([ProcessMathPdfJob::class]);
        Storage::fake('public');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $module = Module::create(['title' => 'Software Design', 'department_id' => $department->id, 'course_id' => $course->id]);

        $this->actingAs($admin)
            ->post(route('admin.modules.subtopics.store', $module), [
                'title' => 'Large Reviewer',
                'file' => UploadedFile::fake()->create('reviewer.pdf', 76800, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.modules.show', $module));

        $subtopic = Subtopic::where('title', 'Large Reviewer')->sole();
        Storage::disk('public')->assertExists($subtopic->file_path);

        $this->post(route('admin.modules.subtopics.store', $module), [
            'title' => 'Oversized Reviewer',
            'file' => UploadedFile::fake()->create('oversized-reviewer.pdf', 76801, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('subtopics', ['title' => 'Oversized Reviewer']);
    }

    public function test_admin_cannot_replace_reviewer_with_a_file_over_75_mb(): void
    {
        Storage::fake('public');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $module = Module::create(['title' => 'Software Design', 'department_id' => $department->id, 'course_id' => $course->id]);
        $subtopic = Subtopic::create([
            'module_id' => $module->id,
            'title' => 'Existing Reviewer',
            'file_path' => 'modules/subtopics/existing.pdf',
        ]);
        Storage::disk('public')->put($subtopic->file_path, 'existing reviewer');

        $this->actingAs($admin)
            ->put(route('admin.subtopics.update', $subtopic), [
                'title' => 'Existing Reviewer',
                'file' => UploadedFile::fake()->create('oversized-reviewer.pdf', 76801, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        Storage::disk('public')->assertExists($subtopic->file_path);
        $this->assertDatabaseHas('subtopics', [
            'id' => $subtopic->id,
            'file_path' => 'modules/subtopics/existing.pdf',
        ]);
    }

    public function test_faculty_sees_modules_from_across_their_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create([
            'name' => 'Software Engineering',
            'code' => 'SE',
            'department_id' => $department->id,
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $otherCourse = Course::create([
            'name' => 'Information Systems',
            'code' => 'IS',
            'department_id' => $department->id,
        ]);
        $module = Module::create([
            'title' => 'Software Design',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $otherModule = Module::create([
            'title' => 'Systems Analysis',
            'department_id' => $department->id,
            'course_id' => $otherCourse->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.modules.index'))
            ->assertSee('Modules')
            ->assertSee('Choose Program')
            ->assertSee('Add New Module')
            ->assertSee('id="createModuleModal"', false)
            ->assertSee($module->title)
            ->assertSee($otherModule->title);

        $this->get(route('admin.modules.index', ['course_id' => $course->id]))
            ->assertSee($module->title)
            ->assertSee('Add New Module')
            ->assertSee('id="createModuleModal"', false);
    }

    public function test_faculty_can_create_modules_in_any_program_in_their_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        Course::create(['name' => 'Information Systems', 'code' => 'IS', 'department_id' => $department->id]);
        $foreignCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);

        $this->actingAs($admin)
            ->post(route('admin.modules.store'), ['title' => 'Assigned Course Module', 'course_id' => $course->id])
            ->assertRedirect(route('admin.modules.index', ['course_id' => $course->id]));
        $this->assertDatabaseHas('modules', [
            'title' => 'Assigned Course Module',
            'course_id' => $course->id,
        ]);

        $this->post(route('admin.modules.store'), [
            'title' => 'Foreign Course Module',
            'course_id' => $foreignCourse->id,
        ])->assertNotFound();

        $this->assertDatabaseMissing('modules', ['title' => 'Foreign Course Module']);
    }

    public function test_adding_courses_keeps_existing_modules_with_the_first_course(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $unassignedModule = Module::create([
            'title' => 'Existing Foundations',
            'department_id' => $department->id,
        ]);
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.departments.courses.store', $department), [
                'name' => 'Software Engineering',
            ])
            ->assertRedirect();

        $firstCourse = Course::where('department_id', $department->id)->sole();
        $this->assertDatabaseHas('modules', [
            'id' => $unassignedModule->id,
            'course_id' => $firstCourse->id,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $firstCourse->id,
        ]);
        $this->actingAs($admin)
            ->post(route('admin.modules.store'), ['title' => 'First Course Module', 'course_id' => $firstCourse->id])
            ->assertRedirect(route('admin.modules.index', ['course_id' => $firstCourse->id]));
        $legacyModule = Module::create([
            'title' => 'Legacy Course Module',
            'department_id' => $department->id,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.departments.courses.store', $department), [
                'name' => 'Information Systems',
            ])
            ->assertRedirect();

        $secondCourse = Course::where('department_id', $department->id)
            ->where('id', '!=', $firstCourse->id)
            ->sole();
        $this->assertDatabaseHas('modules', [
            'title' => 'First Course Module',
            'course_id' => $firstCourse->id,
        ]);
        $this->assertDatabaseHas('modules', [
            'id' => $legacyModule->id,
            'course_id' => $firstCourse->id,
        ]);

        $firstCourseStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $firstCourse->id,
        ]);
        $secondCourseStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $secondCourse->id,
        ]);

        $this->actingAs($firstCourseStudent)
            ->get(route('student.learn.index'))
            ->assertSee('Existing Foundations')
            ->assertSee('First Course Module')
            ->assertSee('Legacy Course Module');

        $this->actingAs($secondCourseStudent)
            ->get(route('student.learn.index'))
            ->assertDontSee('Existing Foundations')
            ->assertDontSee('First Course Module')
            ->assertDontSee('Legacy Course Module');
    }

    public function test_student_only_sees_and_opens_modules_assigned_to_their_course(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $softwareEngineering = Course::create([
            'name' => 'Software Engineering',
            'code' => 'SE',
            'department_id' => $department->id,
        ]);
        $informationSystems = Course::create([
            'name' => 'Information Systems',
            'code' => 'IS',
            'department_id' => $department->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $softwareEngineering->id,
        ]);
        $softwareModule = Module::create([
            'title' => 'Software Design',
            'department_id' => $department->id,
            'course_id' => $softwareEngineering->id,
        ]);
        $informationModule = Module::create([
            'title' => 'Systems Analysis',
            'department_id' => $department->id,
            'course_id' => $informationSystems->id,
        ]);
        $unassignedModule = Module::create(['title' => 'Unassigned Orientation', 'department_id' => $department->id]);
        $foreignSubtopic = Subtopic::create(['module_id' => $informationModule->id, 'title' => 'Requirements']);

        $this->actingAs($student)
            ->get(route('student.learn.index'))
            ->assertSee('Modules')
            ->assertSee('Search modules...')
            ->assertDontSee('Courses')
            ->assertSee($softwareModule->title)
            ->assertDontSee($unassignedModule->title)
            ->assertDontSee($informationModule->title);

        $this->get(route('student.learn.show', $informationModule))
            ->assertNotFound();

        $this->get(route('student.learn.show', $softwareModule))
            ->assertSee('Back to all modules')
            ->assertSee('No description provided for this module.');

        $this->get(route('student.learn.show', $unassignedModule))
            ->assertNotFound();

        $this->get(route('student.learn.review', $foreignSubtopic))
            ->assertNotFound();

        $this->get(route('student.learn.quiz', $foreignSubtopic))
            ->assertNotFound();
    }

    public function test_student_general_test_does_not_use_another_course_modules(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $softwareEngineering = Course::create([
            'name' => 'Software Engineering',
            'code' => 'SE',
            'department_id' => $department->id,
        ]);
        $informationSystems = Course::create([
            'name' => 'Information Systems',
            'code' => 'IS',
            'department_id' => $department->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $softwareEngineering->id,
        ]);
        $otherCourseModule = Module::create([
            'title' => 'Systems Analysis',
            'department_id' => $department->id,
            'course_id' => $informationSystems->id,
        ]);
        Subtopic::create(['module_id' => $otherCourseModule->id, 'title' => 'Requirements']);

        $this->actingAs($student)
            ->get(route('student.tests.generate-general'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('tests', 0);
    }

    public function test_student_cannot_access_modules_assigned_to_another_course_or_without_a_course(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $softwareEngineering = Course::create([
            'name' => 'Software Engineering',
            'code' => 'SE',
            'department_id' => $department->id,
        ]);
        $informationSystems = Course::create([
            'name' => 'Information Systems',
            'code' => 'IS',
            'department_id' => $department->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $informationSystems->id,
        ]);
        $firstCourseModule = Module::create([
            'title' => 'Software Design',
            'department_id' => $department->id,
            'course_id' => $softwareEngineering->id,
        ]);
        $unassignedModule = Module::create([
            'title' => 'Unassigned Foundations',
            'department_id' => $department->id,
        ]);

        $this->actingAs($student)
            ->get(route('student.learn.index'))
            ->assertDontSee($firstCourseModule->title)
            ->assertDontSee($unassignedModule->title);

        $this->get(route('student.learn.show', $firstCourseModule))
            ->assertNotFound();

        $this->get(route('student.learn.show', $unassignedModule))
            ->assertNotFound();
    }

    public function test_student_without_an_assigned_course_cannot_access_unassigned_modules(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        Course::create([
            'name' => 'Software Engineering',
            'code' => 'SE',
            'department_id' => $department->id,
        ]);
        Course::create([
            'name' => 'Information Systems',
            'code' => 'IS',
            'department_id' => $department->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => null,
        ]);
        $unassignedModule = Module::create([
            'title' => 'Unassigned Foundations',
            'department_id' => $department->id,
        ]);

        $this->actingAs($student)
            ->get(route('student.learn.index'))
            ->assertDontSee($unassignedModule->title);

        $this->get(route('student.learn.show', $unassignedModule))
            ->assertNotFound();
    }

    public function test_student_cannot_start_a_study_session_for_another_courses_subtopic(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $softwareEngineering = Course::create([
            'name' => 'Software Engineering',
            'code' => 'SE',
            'department_id' => $department->id,
        ]);
        $informationSystems = Course::create([
            'name' => 'Information Systems',
            'code' => 'IS',
            'department_id' => $department->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $softwareEngineering->id,
        ]);
        $otherCourseModule = Module::create([
            'title' => 'Systems Analysis',
            'department_id' => $department->id,
            'course_id' => $informationSystems->id,
        ]);
        $subtopic = Subtopic::create(['module_id' => $otherCourseModule->id, 'title' => 'Requirements']);

        $this->actingAs($student)
            ->postJson(route('student.activity.sessions.store'), ['subtopic_id' => $subtopic->id])
            ->assertNotFound();

        $this->assertDatabaseCount('study_sessions', 0);
    }

    private function assertModalIsOutsideTransformedContainers(TestResponse $response, string $modalId): void
    {
        $document = new \DOMDocument;
        $previousLibxmlErrorSetting = libxml_use_internal_errors(true);
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlErrorSetting);

        $xpath = new \DOMXPath($document);
        $modal = $xpath->query("//*[@id='{$modalId}']")->item(0);

        $this->assertNotNull($modal);
        $this->assertSame(
            0,
            $xpath->query(
                "//*[@id='{$modalId}']/ancestor::*[contains(concat(' ', normalize-space(@class), ' '), ' modules-perspective-scene ') or contains(concat(' ', normalize-space(@class), ' '), ' animate-entrance ')]",
            )->length,
        );
    }
}
