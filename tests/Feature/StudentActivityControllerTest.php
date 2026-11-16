<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Module;
use App\Models\Result;
use App\Models\StudySession;
use App\Models\Subtopic;
use App\Models\Test as StudentTest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentActivityControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_monthly_calendar_module_and_topic_views_are_scoped_to_the_student(): void
    {
        $this->travelTo('2026-10-25 12:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'created_at' => '2026-08-16 10:00:00',
        ]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Database Systems', 'department_id' => $department->id]);
        $foreignModule = Module::create(['title' => 'Foreign Module', 'department_id' => $otherDepartment->id]);
        $normalization = Subtopic::create(['module_id' => $module->id, 'title' => 'Normalization']);
        $sql = Subtopic::create(['module_id' => $module->id, 'title' => 'SQL Queries']);
        StudySession::create([
            'user_id' => $student->id,
            'module_id' => $module->id,
            'subtopic_id' => $normalization->id,
            'activity_date' => '2026-10-25',
            'active_seconds' => 5400,
            'started_at' => '2026-10-25 09:00:00',
            'last_heartbeat_at' => '2026-10-25 10:30:00',
            'pause_reason' => 'quiz',
        ]);
        StudySession::create([
            'user_id' => $student->id,
            'module_id' => $module->id,
            'subtopic_id' => $sql->id,
            'activity_date' => '2026-10-25',
            'active_seconds' => 7200,
            'started_at' => '2026-10-25 09:00:00',
            'last_heartbeat_at' => '2026-10-25 11:00:00',
        ]);
        StudySession::create([
            'user_id' => $student->id,
            'module_id' => $module->id,
            'subtopic_id' => $sql->id,
            'activity_date' => '2026-09-10',
            'active_seconds' => 3600,
            'started_at' => '2026-09-10 09:00:00',
            'last_heartbeat_at' => '2026-09-10 10:00:00',
        ]);
        StudySession::create([
            'user_id' => $otherStudent->id,
            'module_id' => $module->id,
            'subtopic_id' => $normalization->id,
            'activity_date' => '2026-10-25',
            'active_seconds' => 99000,
            'started_at' => '2026-10-25 09:00:00',
            'last_heartbeat_at' => '2026-10-26 12:30:00',
        ]);
        $passedQuiz = StudentTest::create([
            'title' => 'Normalization Quiz',
            'department_id' => $department->id,
            'subtopic_id' => $normalization->id,
            'is_general' => false,
        ]);
        $needsReviewQuiz = StudentTest::create([
            'title' => 'SQL Quiz',
            'department_id' => $department->id,
            'subtopic_id' => $sql->id,
            'is_general' => false,
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $passedQuiz->id,
            'score' => 9,
            'total_questions' => 10,
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $needsReviewQuiz->id,
            'score' => 8,
            'total_questions' => 10,
        ]);

        $monthlyResponse = $this->actingAs($student)
            ->getJson(route('student.activity.index'));
        $monthlyResponse
            ->assertOk()
            ->assertJsonPath('level', 'months')
            ->assertJsonPath('months.0.month', '2026-08')
            ->assertJsonPath('months.1.active_seconds', 3600)
            ->assertJsonPath('months.2.active_seconds', 12600);

        $this->getJson(route('student.activity.index', ['month' => '2026-10']))
            ->assertOk()
            ->assertJsonPath('first_weekday', 4)
            ->assertJsonPath('days.24.active_seconds', 12600);

        $this->getJson(route('student.activity.index', ['month' => '2026-10', 'date' => '2026-10-25']))
            ->assertOk()
            ->assertJsonPath('modules.0.title', 'Database Systems')
            ->assertJsonPath('modules.0.active_seconds', 12600)
            ->assertJsonMissing(['title' => $foreignModule->title]);

        $this->getJson(route('student.activity.index', [
            'month' => '2026-10',
            'date' => '2026-10-25',
            'module_id' => $module->id,
        ]))
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'Normalization',
                'active_seconds' => 5400,
                'quiz_status' => 'Passed',
                'quiz_auto_paused' => true,
                'quiz_score' => 90,
            ])
            ->assertJsonFragment([
                'title' => 'SQL Queries',
                'active_seconds' => 7200,
                'quiz_status' => 'Needs Review',
                'quiz_auto_paused' => false,
                'quiz_score' => 80,
            ]);
    }

    public function test_topic_quiz_history_returns_only_the_students_five_most_recent_attempts(): void
    {
        $this->travelTo('2026-10-26 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Database Systems', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Normalization']);
        $foreignModule = Module::create(['title' => 'Foreign Module', 'department_id' => $otherDepartment->id]);
        $foreignSubtopic = Subtopic::create(['module_id' => $foreignModule->id, 'title' => 'Foreign Topic']);

        foreach (range(1, 6) as $attempt) {
            $quiz = StudentTest::create([
                'title' => "Normalization Quiz {$attempt}",
                'department_id' => $department->id,
                'subtopic_id' => $subtopic->id,
                'is_general' => false,
            ]);
            $result = Result::create([
                'user_id' => $student->id,
                'test_id' => $quiz->id,
                'score' => $attempt,
                'total_questions' => 10,
            ]);
            $result->forceFill(['created_at' => now()->subDays(6 - $attempt)])->save();
        }

        $foreignQuiz = StudentTest::create([
            'title' => 'Foreign Quiz',
            'department_id' => $otherDepartment->id,
            'subtopic_id' => $foreignSubtopic->id,
            'is_general' => false,
        ]);
        Result::create([
            'user_id' => $otherStudent->id,
            'test_id' => $foreignQuiz->id,
            'score' => 10,
            'total_questions' => 10,
        ]);

        $response = $this->actingAs($student)
            ->getJson(route('student.activity.topics.quizzes', $subtopic));

        $response
            ->assertOk()
            ->assertJsonPath('topic', 'Normalization')
            ->assertJsonPath('quizzes.0.title', 'Normalization Quiz 6')
            ->assertJsonPath('quizzes.0.url', route('student.results.show', $result))
            ->assertJsonMissing(['title' => 'Normalization Quiz 1'])
            ->assertJsonMissing(['title' => 'Foreign Quiz']);
        $this->assertCount(5, $response->json('quizzes'));

        $this->getJson(route('student.activity.topics.quizzes', $foreignSubtopic))
            ->assertNotFound();
    }

    public function test_admin_activity_drilldown_is_scoped_to_the_selected_student_and_department(): void
    {
        $this->travelTo('2026-10-25 12:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $foreignCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_at' => '2026-08-16 10:00:00',
        ]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $foreignStudent = User::factory()->create(['role' => 'student', 'department_id' => $otherDepartment->id, 'course_id' => $foreignCourse->id]);
        $module = Module::create(['title' => 'Database Systems', 'department_id' => $department->id, 'course_id' => $course->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Normalization']);
        $foreignModule = Module::create(['title' => 'Foreign Module', 'department_id' => $otherDepartment->id, 'course_id' => $foreignCourse->id]);
        $foreignSubtopic = Subtopic::create(['module_id' => $foreignModule->id, 'title' => 'Foreign Topic']);
        StudySession::create([
            'user_id' => $student->id,
            'module_id' => $module->id,
            'subtopic_id' => $subtopic->id,
            'activity_date' => '2026-10-25',
            'active_seconds' => 5400,
            'started_at' => '2026-10-25 09:00:00',
            'last_heartbeat_at' => '2026-10-25 10:30:00',
        ]);
        StudySession::create([
            'user_id' => $otherStudent->id,
            'module_id' => $module->id,
            'subtopic_id' => $subtopic->id,
            'activity_date' => '2026-10-25',
            'active_seconds' => 7200,
            'started_at' => '2026-10-25 09:00:00',
            'last_heartbeat_at' => '2026-10-25 11:00:00',
        ]);
        $quiz = StudentTest::create([
            'title' => 'Normalization Quiz',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'subtopic_id' => $subtopic->id,
            'is_general' => false,
        ]);
        $foreignQuiz = StudentTest::create([
            'title' => 'Foreign Topic Quiz',
            'department_id' => $otherDepartment->id,
            'course_id' => $foreignCourse->id,
            'subtopic_id' => $foreignSubtopic->id,
            'is_general' => false,
        ]);
        Result::create([
            'user_id' => $otherStudent->id,
            'test_id' => $quiz->id,
            'score' => 2,
            'total_questions' => 10,
        ]);
        $studentResult = Result::create([
            'user_id' => $student->id,
            'test_id' => $quiz->id,
            'score' => 9,
            'total_questions' => 10,
            'answers' => [
                [
                    'question_text' => 'Which protocol stores structured data?',
                    'user_answer' => 'SQL',
                    'correct_answer' => 'SQL',
                    'options' => ['SQL', 'HTTP'],
                    'explanation' => 'SQL stores structured data in relational tables.',
                    'is_correct' => true,
                ],
            ],
        ]);
        Result::create([
            'user_id' => $foreignStudent->id,
            'test_id' => $foreignQuiz->id,
            'score' => 10,
            'total_questions' => 10,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.students.activity.index', [
                'student' => $student,
                'month' => '2026-10',
                'date' => '2026-10-25',
                'module_id' => $module->id,
            ]))
            ->assertOk()
            ->assertJsonPath('level', 'topics')
            ->assertJsonPath('topics.0.title', 'Normalization')
            ->assertJsonPath('topics.0.active_seconds', 5400)
            ->assertJsonPath('topics.0.quiz_status', 'Passed')
            ->assertJsonPath('topics.0.quiz_score', 90);

        $quizHistory = $this->getJson(route('admin.students.activity.topics.quizzes', [
            'student' => $student,
            'subtopic' => $subtopic,
        ]))
            ->assertOk()
            ->assertJsonPath('topic', 'Normalization')
            ->assertJsonPath('quizzes.0.title', 'Normalization Quiz')
            ->assertJsonPath('quizzes.0.url', route('admin.students.results.show', [
                'student' => $student,
                'result' => $studentResult,
            ]));

        $this->assertCount(1, $quizHistory->json('quizzes'));

        $this->get(route('admin.students.results.show', [
            'student' => $student,
            'result' => $studentResult,
        ]))
            ->assertSee('Detailed Question Analysis')
            ->assertSee('Performance Summary')
            ->assertSee('Overall Accuracy')
            ->assertSee('Which protocol stores structured data?')
            ->assertSee('HTTP')
            ->assertSee('SQL stores structured data in relational tables.')
            ->assertSee('Rationale')
            ->assertSee('Return to Student Profile')
            ->assertDontSee('Print Results')
            ->assertDontSee('Review again');

        $this->actingAs($superAdmin)
            ->get(route('super-admin.students.results.show', [
                'student' => $student,
                'result' => $studentResult,
            ]))
            ->assertSee('Performance Summary')
            ->assertSee('Overall Accuracy')
            ->assertSee('Detailed Question Analysis')
            ->assertSee('Which protocol stores structured data?')
            ->assertSee('HTTP')
            ->assertSee('SQL stores structured data in relational tables.')
            ->assertSee('Return to Student Profile');

        $this->actingAs($admin);

        $this->getJson(route('admin.students.activity.index', [
            'student' => $foreignStudent,
        ]))->assertNotFound();
        $this->getJson(route('admin.students.activity.topics.quizzes', [
            'student' => $student,
            'subtopic' => $foreignSubtopic,
        ]))->assertNotFound();
    }

    public function test_admin_result_view_rejects_results_outside_the_selected_student_and_department(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $foreignCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $foreignStudent = User::factory()->create(['role' => 'student', 'department_id' => $otherDepartment->id, 'course_id' => $foreignCourse->id]);
        $test = StudentTest::create([
            'title' => 'Normalization Quiz',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => false,
        ]);
        $studentResult = Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 8,
            'total_questions' => 10,
        ]);
        $otherStudentResult = Result::create([
            'user_id' => $otherStudent->id,
            'test_id' => $test->id,
            'score' => 7,
            'total_questions' => 10,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.students.results.show', [
                'student' => $student,
                'result' => $otherStudentResult,
            ]))
            ->assertNotFound();

        $this->get(route('admin.students.results.show', [
            'student' => $foreignStudent,
            'result' => $studentResult,
        ]))
            ->assertNotFound();
    }

    public function test_reading_sessions_credit_heartbeats_and_pause_for_quiz_navigation(): void
    {
        $this->travelTo('2026-10-25 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Web Technologies', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'REST APIs']);

        $startResponse = $this->actingAs($student)
            ->postJson(route('student.activity.sessions.store'), ['subtopic_id' => $subtopic->id])
            ->assertCreated()
            ->assertJsonPath('active_seconds', 0);
        $sessionId = $startResponse->json('session_id');

        $this->travel(30)->seconds();
        $this->postJson(route('student.activity.sessions.heartbeat', ['studySession' => $sessionId]))
            ->assertOk()
            ->assertJsonPath('active_seconds', 30);

        $this->travel(30)->seconds();
        $this->postJson(route('student.activity.sessions.stop', ['studySession' => $sessionId]), ['reason' => 'quiz'])
            ->assertOk()
            ->assertJsonPath('active_seconds', 60)
            ->assertJsonPath('stopped', true);

        $this->assertDatabaseHas('study_sessions', [
            'id' => $sessionId,
            'user_id' => $student->id,
            'subtopic_id' => $subtopic->id,
            'active_seconds' => 60,
            'pause_reason' => 'quiz',
        ]);
    }

    public function test_heartbeat_caps_credit_and_does_not_continue_a_session_after_midnight(): void
    {
        $this->travelTo('2026-10-25 23:56:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Operating Systems', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Process Scheduling']);
        $startResponse = $this->actingAs($student)
            ->postJson(route('student.activity.sessions.store'), ['subtopic_id' => $subtopic->id])
            ->assertCreated();
        $sessionId = $startResponse->json('session_id');

        $this->travel(90)->seconds();
        $this->postJson(route('student.activity.sessions.heartbeat', ['studySession' => $sessionId]))
            ->assertOk()
            ->assertJsonPath('active_seconds', 45);

        $this->travel(180)->seconds();
        $this->postJson(route('student.activity.sessions.heartbeat', ['studySession' => $sessionId]))
            ->assertStatus(409);

        $this->assertDatabaseHas('study_sessions', [
            'id' => $sessionId,
            'active_seconds' => 45,
            'pause_reason' => 'midnight',
            'ended_at' => '2026-10-26 00:00:30',
        ]);
    }

    public function test_student_cannot_track_another_departments_subtopic_or_another_students_session(): void
    {
        $this->travelTo('2026-10-25 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $foreignModule = Module::create(['title' => 'Education Module', 'department_id' => $otherDepartment->id]);
        $foreignSubtopic = Subtopic::create(['module_id' => $foreignModule->id, 'title' => 'Lesson Planning']);
        $module = Module::create(['title' => 'Database Systems', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'SQL']);
        $otherSession = StudySession::create([
            'user_id' => $otherStudent->id,
            'module_id' => $module->id,
            'subtopic_id' => $subtopic->id,
            'activity_date' => '2026-10-25',
            'active_seconds' => 0,
            'started_at' => now(),
            'last_heartbeat_at' => now(),
        ]);

        $this->actingAs($student)
            ->postJson(route('student.activity.sessions.store'), ['subtopic_id' => $foreignSubtopic->id])
            ->assertNotFound();

        $this->postJson(route('student.activity.sessions.stop', ['studySession' => $otherSession->id]), ['reason' => 'quiz'])
            ->assertNotFound();

        $this->assertDatabaseHas('study_sessions', [
            'id' => $otherSession->id,
            'user_id' => $otherStudent->id,
            'ended_at' => null,
        ]);
    }

    public function test_dashboard_and_review_page_expose_the_activity_and_reading_timer_controls(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Networking and Security', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'TCP/IP']);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertSee('Study Activity')
            ->assertSee('Monthly active study time')
            ->assertSee('activity-month-y-tick', false)
            ->assertSee('activity-heatmap-legend', false)
            ->assertSee('is-level-3', false)
            ->assertSee('activity-calendar', false)
            ->assertSee('activity-column-chart', false)
            ->assertSee('activity-column-plot', false)
            ->assertSee('createActivityColumn', false)
            ->assertSee('display: block;', false)
            ->assertSee('activity-topic-quizzes-modal', false)
            ->assertSee(route('student.activity.topics.quizzes', ['subtopic' => '__SUBTOPIC__']), false)
            ->assertSee('rows.length > 10', false)
            ->assertSee('scrollIntoView', false)
            ->assertSee('storage/images/systemlogo.jpg')
            ->assertSee('Bar height is scaled to monthly hours or minutes.')
            ->assertSee('data-analytics-url="'.route('student.activity.index').'"', false);

        $this->get(route('student.learn.review', $subtopic))
            ->assertSee('data-subtopic-id="'.$subtopic->id.'"', false)
            ->assertSee('data-start-url="'.route('student.activity.sessions.store').'"', false)
            ->assertSee('Reading time is tracked while this page is visible; quizzes pause the timer.');
    }

    public function test_activity_session_endpoints_validate_input_without_changing_session_state(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Web Technologies', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'REST APIs']);

        $this->actingAs($student)
            ->postJson(route('student.activity.sessions.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subtopic_id');

        $sessionResponse = $this->postJson(route('student.activity.sessions.store'), ['subtopic_id' => $subtopic->id])
            ->assertCreated();
        $sessionId = $sessionResponse->json('session_id');

        $this->postJson(route('student.activity.sessions.stop', ['studySession' => $sessionId]), ['reason' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->assertDatabaseHas('study_sessions', [
            'id' => $sessionId,
            'ended_at' => null,
            'active_seconds' => 0,
        ]);
    }

    public function test_drilldown_rejects_months_outside_registration_to_current_month(): void
    {
        $this->travelTo('2026-10-25 12:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'created_at' => '2026-09-16 10:00:00',
        ]);

        $this->actingAs($student)
            ->getJson(route('student.activity.index', ['month' => '2026-08']))
            ->assertNotFound();

        $this->getJson(route('student.activity.index', ['month' => '2026-11']))
            ->assertNotFound();

        $this->getJson(route('student.activity.index', ['date' => '2026-10-25']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('month');
    }
}
