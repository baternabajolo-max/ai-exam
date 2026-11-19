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

    public function test_module_activity_returns_only_quiz_verified_time_and_all_time_topic_totals(): void
    {
        $this->travelTo('2026-10-25 12:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $otherCourse = Course::create(['name' => 'Information Systems', 'code' => 'IS', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_at' => '2026-08-16 10:00:00',
        ]);
        $otherStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $otherCourse->id,
        ]);
        $module = Module::create(['title' => 'Database Systems', 'department_id' => $department->id, 'course_id' => $course->id]);
        $verifiedTopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Verified Topic']);
        $unverifiedTopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Unverified Topic']);
        $otherVerifiedTopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Another Verified Topic']);
        $quiz = StudentTest::create([
            'title' => 'Verified Topic Quiz',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'subtopic_id' => $verifiedTopic->id,
            'is_general' => false,
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $quiz->id,
            'score' => 8,
            'total_questions' => 10,
        ]);
        $otherQuiz = StudentTest::create([
            'title' => 'Another Verified Topic Quiz',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'subtopic_id' => $otherVerifiedTopic->id,
            'is_general' => false,
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $otherQuiz->id,
            'score' => 7,
            'total_questions' => 10,
        ]);

        foreach ([
            [$verifiedTopic, '2026-09-10', 600],
            [$verifiedTopic, '2026-10-25', 1200],
            [$verifiedTopic, '2026-10-20', 900],
            [$unverifiedTopic, '2026-10-25', 900],
            [$otherVerifiedTopic, '2026-10-20', 700],
        ] as [$topic, $date, $seconds]) {
            StudySession::create([
                'user_id' => $student->id,
                'module_id' => $module->id,
                'subtopic_id' => $topic->id,
                'activity_date' => $date,
                'active_seconds' => $seconds,
                'started_at' => "{$date} 09:00:00",
                'last_heartbeat_at' => "{$date} 09:30:00",
            ]);
        }

        $this->actingAs($admin)
            ->getJson(route('admin.modules.students.activity', [
                'module' => $module,
                'student' => $student,
                'month' => '2026-10',
            ]))
            ->assertOk()
            ->assertJsonPath('months.1.active_seconds', 600)
            ->assertJsonPath('months.2.active_seconds', 1900)
            ->assertJsonPath('calendar.days.24.active_seconds', 300)
            ->assertJsonPath('topics.0.title', 'Another Verified Topic')
            ->assertJsonPath('topics.0.active_seconds', 700)
            ->assertJsonPath('topics.1.title', 'Unverified Topic')
            ->assertJsonPath('topics.1.active_seconds', 0)
            ->assertJsonPath('topics.2.title', 'Verified Topic')
            ->assertJsonPath('topics.2.active_seconds', 1200)
            ->assertJsonPath('topics.2.all_time_seconds', 1800);

        $this->getJson(route('admin.modules.students.activity', [
            'module' => $module,
            'student' => $student,
            'month' => '2026-10',
            'date' => '2026-10-25',
        ]))
            ->assertOk()
            ->assertJsonPath('date', '2026-10-25')
            ->assertJsonPath('topics.0.active_seconds', 0)
            ->assertJsonPath('topics.2.active_seconds', 300);

        $this->getJson(route('admin.modules.students.activity', [
            'module' => $module,
            'student' => $student,
            'month' => '2026-10',
            'subtopic_id' => $verifiedTopic->id,
        ]))
            ->assertOk()
            ->assertJsonPath('subtopic_id', $verifiedTopic->id)
            ->assertJsonPath('months.1.active_seconds', 600)
            ->assertJsonPath('months.2.active_seconds', 1200)
            ->assertJsonPath('calendar.days.24.active_seconds', 300);

        $this->getJson(route('admin.modules.students.activity', [
            'module' => $module,
            'student' => $student,
            'month' => '2026-10',
            'date' => '2026-09-10',
        ]))->assertNotFound();

        $this->getJson(route('admin.modules.students.activity', [
            'module' => $module,
            'student' => $otherStudent,
        ]))->assertNotFound();
    }

    public function test_admin_module_overview_returns_completion_metrics_and_selected_topic_attempt_history(): void
    {
        $this->travelTo('2026-11-19 12:00:00');
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
        $completedTopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Completed Topic']);
        $inProgressTopic = Subtopic::create(['module_id' => $module->id, 'title' => 'In Progress Topic']);
        $notStartedTopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Not Started Topic']);
        $foreignModule = Module::create(['title' => 'Foreign Course', 'department_id' => $department->id, 'course_id' => $course->id]);
        $foreignTopic = Subtopic::create(['module_id' => $foreignModule->id, 'title' => 'Foreign Topic']);

        foreach ([
            [$completedTopic, 300],
            [$inProgressTopic, 240],
        ] as [$topic, $seconds]) {
            StudySession::create([
                'user_id' => $student->id,
                'module_id' => $module->id,
                'subtopic_id' => $topic->id,
                'activity_date' => '2026-11-19',
                'active_seconds' => $seconds,
                'started_at' => '2026-11-19 09:00:00',
                'last_heartbeat_at' => '2026-11-19 09:30:00',
            ]);
        }

        foreach ([
            [$completedTopic, 8],
            [$completedTopic, 10],
            [$inProgressTopic, 7],
        ] as [$topic, $score]) {
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
        }

        $overviewUrl = route('admin.modules.students.overview', [
            'module' => $module,
            'student' => $student,
        ]);

        $this->actingAs($admin)
            ->getJson($overviewUrl)
            ->assertOk()
            ->assertJsonPath('verified_seconds', 540)
            ->assertJsonPath('topics_completed', 1)
            ->assertJsonPath('topic_count', 3)
            ->assertJsonPath('average_score', 83.33)
            ->assertJsonPath('quiz_attempts', 3)
            ->assertJsonPath('status', 'Needs Review')
            ->assertJsonPath('topics.0.status', 'Completed')
            ->assertJsonPath('topics.1.status', 'In Progress')
            ->assertJsonPath('topics.2.status', 'Not Started');

        $this->getJson($overviewUrl.'?subtopic_id='.$completedTopic->id)
            ->assertOk()
            ->assertJsonPath('selected_topic.title', 'Completed Topic')
            ->assertJsonPath('selected_topic.status', 'Completed')
            ->assertJsonPath('selected_topic.best_score', 100)
            ->assertJsonPath('attempts.0.points', 40)
            ->assertJsonPath('attempts.1.points', 50);

        $this->getJson($overviewUrl.'?subtopic_id='.$notStartedTopic->id)
            ->assertOk()
            ->assertJsonPath('selected_topic.status', 'Not Started')
            ->assertJsonPath('attempts', []);

        $this->getJson($overviewUrl.'?subtopic_id='.$foreignTopic->id)->assertNotFound();
        $this->getJson($overviewUrl.'?subtopic_id=not-a-number')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subtopic_id');

        $this->getJson(route('admin.modules.students.overview', [
            'module' => $module,
            'student' => $foreignStudent,
        ]))->assertNotFound();

        $this->actingAs($student)
            ->getJson($overviewUrl)
            ->assertRedirect(route('student.dashboard'));
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
        $this->postJson(route('student.study-heartbeat'), ['session_id' => $sessionId])
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
        $this->postJson(route('student.study-heartbeat'), ['session_id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('active_seconds', 30);

        $this->travel(180)->seconds();
        $this->postJson(route('student.study-heartbeat'), ['session_id' => $sessionId])
            ->assertStatus(409);

        $this->assertDatabaseHas('study_sessions', [
            'id' => $sessionId,
            'active_seconds' => 30,
            'pause_reason' => 'midnight',
            'ended_at' => '2026-10-26 00:00:30',
        ]);
    }

    public function test_heartbeats_require_twenty_five_seconds_and_enforce_the_cumulative_topic_cap(): void
    {
        $this->travelTo('2026-10-25 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Web Technologies', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'REST APIs']);
        $cappedSubtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'HTTP Caching']);

        $sessionId = $this->actingAs($student)
            ->postJson(route('student.activity.sessions.store'), ['subtopic_id' => $subtopic->id])
            ->assertCreated()
            ->json('session_id');

        $this->travel(24)->seconds();
        $this->postJson(route('student.study-heartbeat'), ['session_id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('active_seconds', 0);

        $this->travel(1)->seconds();
        $this->postJson(route('student.study-heartbeat'), ['session_id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('active_seconds', 25);

        StudySession::create([
            'user_id' => $student->id,
            'module_id' => $module->id,
            'subtopic_id' => $cappedSubtopic->id,
            'activity_date' => '2026-10-25',
            'active_seconds' => 1790,
            'started_at' => '2026-10-25 08:00:00',
            'last_heartbeat_at' => '2026-10-25 08:30:00',
            'ended_at' => '2026-10-25 08:30:00',
        ]);
        $cappedSessionId = $this->postJson(route('student.activity.sessions.store'), ['subtopic_id' => $cappedSubtopic->id])
            ->assertCreated()
            ->assertJsonPath('remaining_seconds', 10)
            ->json('session_id');

        $this->travel(25)->seconds();
        $this->postJson(route('student.study-heartbeat'), ['session_id' => $cappedSessionId])
            ->assertStatus(409)
            ->assertJsonPath('active_seconds', 1800)
            ->assertJsonPath('capped', true);

        $this->assertDatabaseHas('study_sessions', [
            'id' => $cappedSessionId,
            'active_seconds' => 10,
            'pause_reason' => 'topic_cap',
        ]);

        $this->postJson(route('student.activity.sessions.store'), ['subtopic_id' => $cappedSubtopic->id])
            ->assertOk()
            ->assertJsonPath('session_id', null)
            ->assertJsonPath('capped', true)
            ->assertJsonPath('active_seconds', 1800);
    }

    public function test_quiz_attempt_verification_updates_the_reviewer_and_caps_legacy_display_time(): void
    {
        $this->travelTo('2026-10-25 10:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Web Technologies', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'REST APIs']);
        $cappedSubtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'HTTP Caching']);
        $quiz = StudentTest::create([
            'title' => 'REST APIs Quiz',
            'department_id' => $department->id,
            'is_general' => false,
            'subtopic_id' => $subtopic->id,
        ]);

        $this->actingAs($student)
            ->get(route('student.learn.review', $subtopic))
            ->assertOk()
            ->assertSee('Studying: 0m / 30m')
            ->assertSee('Take topic quiz to verify');

        $sessionId = $this->postJson(route('student.activity.sessions.store'), ['subtopic_id' => $subtopic->id])
            ->assertCreated()
            ->assertJsonPath('verified', false)
            ->json('session_id');

        Result::create([
            'user_id' => $student->id,
            'test_id' => $quiz->id,
            'score' => 7,
            'total_questions' => 10,
        ]);

        $this->travel(30)->seconds();
        $this->postJson(route('student.study-heartbeat'), ['session_id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('active_seconds', 30)
            ->assertJsonPath('verified', true);

        $this->get(route('student.learn.review', $subtopic))
            ->assertOk()
            ->assertSee('Studying: 0m / 30m')
            ->assertSee('Verified')
            ->assertSee('data-verified="true"', false);

        StudySession::create([
            'user_id' => $student->id,
            'module_id' => $module->id,
            'subtopic_id' => $cappedSubtopic->id,
            'activity_date' => '2026-10-25',
            'active_seconds' => 1801,
            'started_at' => '2026-10-25 08:00:00',
            'last_heartbeat_at' => '2026-10-25 08:30:00',
            'ended_at' => '2026-10-25 08:30:00',
        ]);

        $this->get(route('student.learn.review', $cappedSubtopic))
            ->assertOk()
            ->assertSee('Studying: 30m / 30m')
            ->assertSee('Take topic quiz to verify');
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

        $this->postJson(route('student.study-heartbeat'), ['session_id' => $otherSession->id])
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

        $reviewResponse = $this->get(route('student.learn.review', $subtopic));
        $reviewResponse
            ->assertSee('data-subtopic-id="'.$subtopic->id.'"', false)
            ->assertSee('data-start-url="'.route('student.activity.sessions.store').'"', false)
            ->assertSee('data-heartbeat-url="'.route('student.study-heartbeat').'"', false)
            ->assertSee('Study Timer Paused')
            ->assertSee('Your study timer pauses immediately when you switch tabs and stops automatically after 45 seconds without scrolling or page progress.')
            ->assertSee('Repeated clicks in the same spot will not keep the timer active')
            ->assertSee('col-lg-4 col-xl-4 review-sidebar-sticky', false)
            ->assertSee('padding-top: 10px !important;', false)
            ->assertSee('top: calc(var(--reader-navbar-height, 53px) + 10px);', false)
            ->assertSee('margin-top: 10px;', false)
            ->assertSee('quiz-checkpoint-footer', false)
            ->assertSee('id="studyTimerPausedWarning" role="note" aria-live="polite"', false)
            ->assertSee('class="btn btn-warning btn-sm rounded-pill px-3 fw-semibold d-none" id="resumeStudyTimer"', false)
            ->assertSee('30-Minute Topic Study Limit Reached')
            ->assertSee('You have reached the 30-minute maximum creditable study limit for this topic')
            ->assertSee('id="studyTopicCapWarning"', false)
            ->assertSee('performance.now() - lastProgressAt >= 45000', false);
        $reviewContent = $reviewResponse->getContent();
        $checkpointPosition = strpos($reviewContent, '<div class="quiz-companion-card">');
        $pausedWarningPosition = strpos($reviewContent, 'id="studyTimerPausedWarning"');
        $capWarningPosition = strpos($reviewContent, 'id="studyTopicCapWarning"');

        $this->assertNotFalse($checkpointPosition);
        $this->assertNotFalse($pausedWarningPosition);
        $this->assertNotFalse($capWarningPosition);
        $this->assertLessThan($pausedWarningPosition, $checkpointPosition);
        $this->assertLessThan($capWarningPosition, $pausedWarningPosition);
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
