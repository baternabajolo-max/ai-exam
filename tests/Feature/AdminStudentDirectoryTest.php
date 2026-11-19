<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Result;
use App\Models\Test as StudentTest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminStudentDirectoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_rejection_actions_use_the_shared_sweetalert_confirmation(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'status' => 'pending',
        ]);
        User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.students'))
            ->assertSee('>Program</th>', false)
            ->assertSee('Software Engineering')
            ->assertDontSee('student-college', false)
            ->assertSee('data-confirm-title="Reject this registration request?"', false)
            ->assertSee('data-confirm-button="Reject request"', false)
            ->assertSee('data-confirm-title="Revoke student access?"', false)
            ->assertSee('data-confirm-button="Revoke access"', false)
            ->assertSee('js/confirm-actions.js')
            ->assertDontSee('onsubmit="return confirm(', false);
    }

    public function test_admin_can_find_a_student_and_send_a_direct_notification(): void
    {
        $engineering = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $engineering->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $engineering->id, 'course_id' => $course->id]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $engineering->id,
            'course_id' => $course->id,
            'name' => 'Ada Student',
            'student_id_number' => '23-12345',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.students'))
            ->assertSee('id="admin-nav-student-search"', false)
            ->assertSee('name="student_search"', false)
            ->assertSee('Search students...')
            ->assertSee('id="admin-nav-student-search-results"', false)
            ->assertSee('aria-autocomplete="list"', false);

        $this->actingAs($admin)
            ->getJson(route('admin.students.search-suggestions', ['q' => 'Ada']))
            ->assertJsonPath('data.0.name', 'Ada Student')
            ->assertJsonPath('data.0.email', $student->email)
            ->assertJsonPath('data.0.url', route('admin.students.show', $student));

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['student_search' => 'Ada']))
            ->assertSee('Ada Student')
            ->assertSee('value="Ada"', false)
            ->assertSee('aria-label="Student search results"', false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['student_search' => '23-12345']))
            ->assertSee('Ada Student');

        $this->actingAs($admin)
            ->post(route('admin.students.notifications.store', $student), [
                'message' => 'Please resume your review this week.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('student_notifications', [
            'student_id' => $student->id,
            'admin_id' => $admin->id,
            'subject' => $admin->name,
            'message' => 'Please resume your review this week.',
        ]);
    }

    public function test_student_search_matches_from_the_start_of_a_name_email_or_student_id(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $grace = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'name' => 'Grace Hopper',
            'email' => 'grace@example.test',
            'student_id_number' => 'G-100',
        ]);
        User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'name' => 'Margot Student',
            'email' => 'margot@gmail.com',
            'student_id_number' => 'M-200',
        ]);
        User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'name' => 'Logan Student',
            'email' => 'logan@example.test',
            'student_id_number' => 'L-300',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.students.search-suggestions', ['q' => 'g']))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Grace Hopper');

        $this->get(route('admin.dashboard', ['student_search' => 'g']))
            ->assertSee('Grace Hopper')
            ->assertViewHas('matchingStudents', fn ($students): bool => $students->pluck('id')->all() === [$grace->id]);
    }

    public function test_admin_cannot_search_or_view_a_student_from_another_department(): void
    {
        $engineering = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $education = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $engineeringCourse = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $engineering->id]);
        $educationCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $education->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $engineering->id, 'course_id' => $engineeringCourse->id]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $education->id,
            'course_id' => $educationCourse->id,
            'name' => 'Other Department Student',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['student_search' => $student->name]))
            ->assertSee('No students match that search.')
            ->assertViewHas('matchingStudents', fn ($matchingStudents): bool => $matchingStudents->isEmpty());

        $this->actingAs($admin)
            ->getJson(route('admin.students.search-suggestions', ['q' => 'Other Department Student']))
            ->assertJsonPath('data', []);

        $this->actingAs($admin)
            ->get(route('admin.students.show', $student))
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('admin.students.notifications.store', $student), [
                'subject' => 'Unauthorized message',
                'message' => 'This should not be sent.',
            ])
            ->assertNotFound();
    }

    public function test_student_cannot_use_admin_student_search_suggestions(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);

        $this->actingAs($student)
            ->getJson(route('admin.students.search-suggestions', ['q' => 'Ada']))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_admin_search_suggestions_validate_the_query_length(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);

        $this->actingAs($admin)
            ->getJson(route('admin.students.search-suggestions', ['q' => str_repeat('a', 101)]))
            ->assertJsonValidationErrors('q');
    }

    public function test_super_admin_can_search_students_and_view_general_test_history_across_colleges(): void
    {
        $engineering = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $education = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $engineeringCourse = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $engineering->id]);
        $educationCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $education->id]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $engineering->id,
            'course_id' => $engineeringCourse->id,
            'name' => 'Maya Student',
            'student_id_number' => 'ENG-100',
        ]);
        User::factory()->create([
            'role' => 'student',
            'department_id' => $education->id,
            'course_id' => $educationCourse->id,
            'name' => 'Maya Other College',
        ]);
        $generalTest = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $engineering->id,
            'course_id' => $engineeringCourse->id,
            'is_general' => true,
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $generalTest->id,
            'score' => 8,
            'total_questions' => 10,
            'answers' => [],
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.dashboard', [
                'college_id' => $engineering->id,
                'student_search' => 'Maya',
            ]))
            ->assertOk()
            ->assertSee('id="admin-nav-student-search"', false)
            ->assertSee('data-suggestions-url="'.route('super-admin.students.search-suggestions').'"', false)
            ->assertSee('Student search results')
            ->assertSee('Maya Student')
            ->assertDontSee('Maya Other College')
            ->assertSee(route('super-admin.students.show', $student));

        $this->getJson(route('super-admin.students.search-suggestions', [
            'q' => 'Maya',
            'college_id' => $engineering->id,
        ]))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.url', route('super-admin.students.show', [
                'student' => $student,
                'college_id' => $engineering->id,
                'student_search' => 'Maya',
            ]));

        $this->get(route('super-admin.students.show', $student))
            ->assertOk()
            ->assertSee('General-test history')
            ->assertSee('General Assessment')
            ->assertSee('80%')
            ->assertSee('Software Engineering');
    }

    public function test_super_admin_student_history_rejects_non_student_records(): void
    {
        $faculty = User::factory()->create(['role' => 'admin']);
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.students.show', $faculty))
            ->assertNotFound();
    }

    public function test_faculty_and_super_admin_profiles_show_every_general_test_response(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'name' => 'Full History Student',
        ]);

        foreach (range(1, 11) as $attemptNumber) {
            $generalTest = StudentTest::create([
                'title' => "General Test Attempt {$attemptNumber}",
                'department_id' => $department->id,
                'course_id' => $attemptNumber === 11 ? null : $course->id,
                'is_general' => true,
            ]);

            Result::create([
                'user_id' => $student->id,
                'test_id' => $generalTest->id,
                'score' => 0,
                'total_questions' => 1,
                'answers' => [[
                    'question_text' => "Question {$attemptNumber}",
                    'user_answer' => $attemptNumber === 1 ? 'Correct response' : 'Wrong response',
                    'correct_answer' => 'Correct response',
                    'is_correct' => $attemptNumber === 1,
                ]],
            ]);
        }

        $scheduledGeneralTest = StudentTest::create([
            'title' => 'Scheduled General Examination',
            'department_id' => $department->id,
            'is_general' => true,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'approval_status' => StudentTest::APPROVAL_STATUS_APPROVED,
        ]);
        $scheduledGeneralResult = Result::create([
            'user_id' => $student->id,
            'test_id' => $scheduledGeneralTest->id,
            'score' => 1,
            'total_questions' => 1,
            'answers' => [],
            'answers_summary' => [[
                'question' => 'Question from legacy summary',
                'student_answer' => 'Legacy response',
                'correct_answer' => 'Expected response',
                'is_correct' => false,
            ]],
        ]);

        $practiceQuiz = StudentTest::create([
            'title' => 'Practice Review Quiz',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => false,
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $practiceQuiz->id,
            'score' => 1,
            'total_questions' => 1,
            'answers' => [],
        ]);

        $facultyResponse = $this->actingAs($faculty)
            ->get(route('admin.students.show', $student))
            ->assertSee('Every general-test attempt')
            ->assertDontSee('View answers')
            ->assertSee('bi-eye', false)
            ->assertSee('aria-label="View Scheduled General Examination results"', false)
            ->assertSee('href="'.route('admin.students.results.show', ['student' => $student, 'result' => $scheduledGeneralResult]).'"', false)
            ->assertDontSee('generalTestReviewModal')
            ->assertSee('Scheduled General Examination')
            ->assertDontSee('Practice Review Quiz')
            ->assertViewHas('generalTestHistory', fn ($history): bool => $history->count() === 12);

        $this->assertSame(
            12,
            $facultyResponse->viewData('generalTestHistory')->count(),
        );

        $this->actingAs($superAdmin)
            ->get(route('super-admin.students.show', $student))
            ->assertSee('student-profile-page', false)
            ->assertDontSee('View answers')
            ->assertSee('bi-eye', false)
            ->assertSee('aria-label="View Scheduled General Examination results"', false)
            ->assertSee('href="'.route('super-admin.students.results.show', ['student' => $student, 'result' => $scheduledGeneralResult]).'"', false)
            ->assertDontSee('generalTestReviewModal')
            ->assertSee('Scheduled General Examination')
            ->assertViewHas('generalTestHistory', fn ($history): bool => $history->count() === 12);
    }

    public function test_exam_history_shows_attempt_review_and_same_department_staff_can_activate_one_retake(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $otherDepartment = Department::create(['name' => 'Business', 'code' => 'BUS']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $faculty->handledCourses()->attach($course);
        $otherFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $otherDepartment->id]);
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = StudentTest::create([
            'title' => 'Engineering Final Examination',
            'topic' => 'Networks',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'approval_status' => StudentTest::APPROVAL_STATUS_APPROVED,
            'available_from' => now()->subHour(),
            'available_until' => now()->addHour(),
        ]);
        $result = Result::create([
            'user_id' => $student->id,
            'test_id' => $exam->id,
            'score' => 3,
            'total_questions' => 4,
            'answers' => [],
        ]);

        $profileResponse = $this->actingAs($faculty)
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee('Examination history')
            ->assertSee('Engineering Final Examination')
            ->assertSee('Activate retake')
            ->assertSee('data-confirm-title="Activate one retake?"', false)
            ->assertSee(route('admin.students.results.show', ['student' => $student, 'result' => $result]), false);
        $profileDocument = new \DOMDocument;
        $profileDocument->loadHTML($profileResponse->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $profileXpath = new \DOMXPath($profileDocument);
        $this->assertSame(
            1,
            $profileXpath->query('//article[contains(@class, "test-row")]//div[contains(@class, "flex-column") and contains(@class, "align-items-center")]/a[contains(@aria-label, "attempt answers")]/following-sibling::form[.//button[contains(@aria-label, "Activate retake")]]')->length,
        );

        $this->post(route('admin.students.tests.retake', ['student' => $student, 'test' => $exam]))
            ->assertRedirect()
            ->assertSessionHas('success', 'One retake has been activated for '.$student->name.'.');

        $this->assertDatabaseHas('test_retake_grants', [
            'test_id' => $exam->id,
            'student_id' => $student->id,
            'granted_by' => $faculty->id,
            'consumed_at' => null,
        ]);

        $this->actingAs($otherFaculty)
            ->post(route('admin.students.tests.retake', ['student' => $student, 'test' => $exam]))
            ->assertNotFound();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.students.tests.retake', ['student' => $student, 'test' => $exam]))
            ->assertRedirect()
            ->assertSessionHas('success', 'A retake is already active for this examination.');

        $this->assertDatabaseCount('test_retake_grants', 1);
    }

    public function test_dashboard_marks_recent_department_assessment_activity_active_and_inactive_students(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $activeStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'name' => 'Active Student',
        ]);
        $inactiveStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'name' => 'Inactive Student',
        ]);
        $test = StudentTest::create([
            'title' => 'TCP Review Quiz',
            'topic' => 'TCP',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => false,
        ]);
        Result::create([
            'user_id' => $activeStudent->id,
            'test_id' => $test->id,
            'score' => 1,
            'total_questions' => 1,
            'answers' => [],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertSee('Active')
            ->assertSee('Inactive')
            ->assertSee(route('admin.students').'#approved-panel', false)
            ->assertViewHas('activeStudentsCount', 1)
            ->assertViewHas('inactiveStudentsCount', 1);
    }

    public function test_admin_dashboard_keeps_message_modal_above_backdrop_and_omits_unused_date_filters(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard', ['period' => 7]))
            ->assertSee('id="messageStudentModal"', false);

        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);

        $this->assertSame(
            0,
            $xpath->query('//*[@id="messageStudentModal"]/ancestor::*[contains(concat(" ", normalize-space(@class), " "), " admin-dashboard ")]')->length,
        );
        $this->assertTrue($document->getElementById('admin-period-start')->hasAttribute('disabled'));
        $this->assertTrue($document->getElementById('admin-period-end')->hasAttribute('disabled'));

        $customEndDate = now();
        $customStartDate = $customEndDate->copy()->subDays(6);
        $customResponse = $this->get(route('admin.dashboard', [
            'period' => 'custom',
            'start_date' => $customStartDate->toDateString(),
            'end_date' => $customEndDate->toDateString(),
        ]));
        $customDocument = new \DOMDocument;
        $customDocument->loadHTML($customResponse->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);

        $this->assertFalse($customDocument->getElementById('admin-period-start')->hasAttribute('disabled'));
        $this->assertFalse($customDocument->getElementById('admin-period-end')->hasAttribute('disabled'));
    }
}
