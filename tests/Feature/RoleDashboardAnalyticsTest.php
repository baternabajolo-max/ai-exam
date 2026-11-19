<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Module;
use App\Models\QuestionBankItem;
use App\Models\Result;
use App\Models\StudySession;
use App\Models\Subtopic;
use App\Models\Test as StudentTest;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\FacultyMonitoringMessageNotification;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RoleDashboardAnalyticsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_dashboard_defaults_to_last_seven_days_and_counts_passing_attempts(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 12:00:00'));

        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $generalTest = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
        ]);
        $reviewTest = StudentTest::create([
            'title' => 'Database Review Quiz',
            'department_id' => $department->id,
            'is_general' => false,
        ]);
        $this->createResult($student, $generalTest, 8, 10, now()->subDay());
        $this->createResult($student, $generalTest, 5, 10, now()->subDays(10));
        $this->createResult($student, $generalTest, 1, 10, now()->subDays(20));
        $this->createResult($student, $reviewTest, 10, 10, now()->subDay());

        $response = $this->actingAs($student)
            ->get(route('student.dashboard', ['period' => 7]))
            ->assertSee('Your general-test performance')
            ->assertSee('id="student-board-readiness"', false)
            ->assertSee('id="readiness-gauge-progress"', false)
            ->assertSee('id="readiness-slider-list"', false)
            ->assertSee('data-readiness-slider', false)
            ->assertSee('Score by test day')
            ->assertSee('Test date')
            ->assertSee('Score (%)')
            ->assertSee('75% passing target')
            ->assertSee('min-width: 0;', false)
            ->assertSee('data-bs-target="#generalTestDateRangeModal"', false)
            ->assertSee('id="generalTestDateRangeModal"', false)
            ->assertSee('Choose date range')
            ->assertSee('Start date')
            ->assertSee('Attempts scoring 75%+')
            ->assertDontSee('No general-test attempts this week.')
            ->assertViewHas('analyticsDays', 7)
            ->assertViewHas('periodStartDate', '2026-03-14')
            ->assertViewHas('periodEndDate', '2026-03-20')
            ->assertViewHas('studentAnalytics', [
                'attempts' => 1,
                'passing_attempts' => 1,
            ]);

        $this->assertCount(7, $response->viewData('studentPerformance'));
        $attemptDay = $response->viewData('studentPerformance')->firstWhere('date', now()->subDay()->toDateString());
        $this->assertSame(80, $attemptDay['score']);
        $this->assertSame(1, $attemptDay['attempts']);
    }

    public function test_admin_activity_period_includes_reading_and_assessment_but_excludes_old_and_foreign_activity(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 12:00:00'));

        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $foreignCourse = Course::create(['name' => 'Education', 'code' => 'EDU', 'department_id' => $otherDepartment->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $assessmentStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $readingStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $oldStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $foreignStudent = User::factory()->create(['role' => 'student', 'department_id' => $otherDepartment->id, 'course_id' => $foreignCourse->id]);
        $departmentTest = StudentTest::create([
            'title' => 'Department Review',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => true,
        ]);
        $foreignTest = StudentTest::create([
            'title' => 'Other College Review',
            'department_id' => $otherDepartment->id,
            'course_id' => $foreignCourse->id,
            'is_general' => true,
        ]);
        $this->createResult($assessmentStudent, $departmentTest, 1, 1, now()->subDay());
        $this->createResult($oldStudent, $departmentTest, 1, 1, now()->subDays(31));
        $this->createResult($foreignStudent, $foreignTest, 1, 1, now()->subDay());
        $module = Module::create(['title' => 'Database Systems', 'department_id' => $department->id, 'course_id' => $course->id]);
        $subtopic = Subtopic::create(['title' => 'SQL', 'module_id' => $module->id]);
        StudySession::create([
            'user_id' => $readingStudent->id,
            'module_id' => $module->id,
            'subtopic_id' => $subtopic->id,
            'activity_date' => now()->toDateString(),
            'active_seconds' => 900,
            'started_at' => now()->subMinutes(15),
            'last_heartbeat_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['period' => 7, 'activity' => 'active']))
            ->assertSee('Study or assessment activity')
            ->assertSee('About active students')
            ->assertSee('About pass rate')
            ->assertViewHas('activeStudentsCount', 2)
            ->assertViewHas('inactiveStudentsCount', 1)
            ->assertViewHas('averageCollegeScore', 100)
            ->assertViewHas('collegePassRate', 100)
            ->assertViewHas('generalTestParticipants', 1)
            ->assertViewHas('activeStudentsCount', 2);
    }

    public function test_admin_student_profile_shows_matching_general_test_statistics_and_history(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 12:00:00'));

        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $generalTest = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => true,
        ]);
        $reviewTest = StudentTest::create([
            'title' => 'General Assessment - Review Quiz',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => true,
        ]);
        $topicTest = StudentTest::create([
            'title' => 'Database Review Quiz',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => false,
        ]);
        $this->createResult($student, $generalTest, 8, 10, CarbonImmutable::parse('2026-03-19 10:00:00'));
        $this->createResult($student, $generalTest, 6, 10, CarbonImmutable::parse('2026-03-20 10:00:00'));
        $this->createResult($student, $generalTest, 5, 10, CarbonImmutable::parse('2026-03-12 10:00:00'));
        $this->createResult($student, $generalTest, 1, 10, CarbonImmutable::parse('2026-02-01 10:00:00'));
        $this->createResult($student, $reviewTest, 10, 10, CarbonImmutable::parse('2026-03-20 11:00:00'));
        $this->createResult($student, $topicTest, 10, 10, CarbonImmutable::parse('2026-03-20 11:30:00'));

        $response = $this->actingAs($admin)
            ->get(route('admin.students.show', ['student' => $student, 'period' => 7]))
            ->assertSee('General-test performance')
            ->assertSee('General-test history')
            ->assertSee('General Assessment - Review Quiz')
            ->assertSee('data-bs-target="#generalTestDateRangeModal"', false)
            ->assertSee('id="generalTestDateRangeModal"', false)
            ->assertSee('Start date')
            ->assertSee('type="date"', false)
            ->assertSee('Attempts scoring 75%+')
            ->assertDontSee('Average score</div>')
            ->assertDontSee('Pass rate</div>')
            ->assertDontSee('Average score change</div>')
            ->assertDontSee('Weekly Performance')
            ->assertViewHas('analyticsDays', 7)
            ->assertViewHas('studentAnalytics', [
                'attempts' => 2,
                'passing_attempts' => 1,
            ])
            ->assertViewHas('periodStartDate', '2026-03-14')
            ->assertViewHas('periodEndDate', '2026-03-20')
            ->assertViewHas('generalTestHistory', fn ($history): bool => $history->count() === 5
                && $history->first()->test->title === 'General Assessment - Review Quiz'
                && $history->contains(fn (Result $result): bool => $result->test->title === 'General Assessment'))
            ->assertDontSee('Other Completed Tests')
            ->assertDontSee('Database Review Quiz');

        $this->assertCount(7, $response->viewData('studentPerformance'));
        $this->assertSame(
            ['2026-03-14', '2026-03-15', '2026-03-16', '2026-03-17', '2026-03-18', '2026-03-19', '2026-03-20'],
            $response->viewData('studentPerformance')->pluck('date')->all(),
        );
        $this->assertSame(null, $response->viewData('studentPerformance')->firstWhere('date', '2026-03-14')['score']);
        $this->assertSame(80, $response->viewData('studentPerformance')->firstWhere('date', '2026-03-19')['score']);
        $this->assertSame(60, $response->viewData('studentPerformance')->firstWhere('date', '2026-03-20')['score']);
        $response->assertSee('Mar 14')->assertSee('Mar 20');
    }

    public function test_admin_student_profile_custom_period_accepts_up_to_seven_days(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-25 12:00:00'));

        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $generalTest = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => true,
        ]);
        $this->createResult($student, $generalTest, 8, 10, CarbonImmutable::parse('2026-03-16 10:00:00'));
        $this->createResult($student, $generalTest, 9, 10, CarbonImmutable::parse('2026-03-16 14:00:00'));
        $this->createResult($student, $generalTest, 7, 10, CarbonImmutable::parse('2026-03-18 10:00:00'));
        $this->createResult($student, $generalTest, 9, 10, CarbonImmutable::parse('2026-03-22 10:00:00'));
        $this->createResult($student, $generalTest, 10, 10, CarbonImmutable::parse('2026-03-15 10:00:00'));
        $this->createResult($student, $generalTest, 10, 10, CarbonImmutable::parse('2026-03-23 10:00:00'));

        $response = $this->actingAs($admin)
            ->get(route('admin.students.show', [
                'student' => $student,
                'period' => 'custom',
                'start_date' => '2026-03-16',
                'end_date' => '2026-03-22',
            ]))
            ->assertViewHas('periodStartDate', '2026-03-16')
            ->assertViewHas('periodEndDate', '2026-03-22')
            ->assertViewHas('analyticsDays', 7)
            ->assertViewHas('studentAnalytics', [
                'attempts' => 4,
                'passing_attempts' => 3,
            ]);

        $this->assertSame(
            ['2026-03-16', '2026-03-17', '2026-03-18', '2026-03-19', '2026-03-20', '2026-03-21', '2026-03-22'],
            $response->viewData('studentPerformance')->pluck('date')->all(),
        );
        $this->assertSame(90, $response->viewData('studentPerformance')->firstWhere('date', '2026-03-16')['score']);
        $this->assertSame(null, $response->viewData('studentPerformance')->firstWhere('date', '2026-03-17')['score']);
        $this->assertSame(90, $response->viewData('studentPerformance')->firstWhere('date', '2026-03-22')['score']);

        $this->get(route('admin.students.show', [
            'student' => $student,
            'period' => 'custom',
            'start_date' => '2026-03-16',
            'end_date' => '2026-03-23',
        ]))->assertSessionHasErrors('end_date');

        $shortRange = $this->get(route('admin.students.show', [
            'student' => $student,
            'period' => 'custom',
            'start_date' => '2026-03-18',
            'end_date' => '2026-03-20',
        ]))
            ->assertViewHas('analyticsDays', 3)
            ->assertViewHas('studentAnalytics', [
                'attempts' => 1,
                'passing_attempts' => 0,
            ]);

        $this->assertSame(
            ['2026-03-18', '2026-03-19', '2026-03-20'],
            $shortRange->viewData('studentPerformance')->pluck('date')->all(),
        );

        $singleDayRange = $this->actingAs($student)
            ->get(route('student.dashboard', [
                'period' => 'custom',
                'start_date' => '2026-03-18',
                'end_date' => '2026-03-18',
            ]))
            ->assertViewHas('analyticsDays', 1)
            ->assertViewHas('studentAnalytics', [
                'attempts' => 1,
                'passing_attempts' => 0,
            ]);

        $this->assertSame(
            ['2026-03-18'],
            $singleDayRange->viewData('studentPerformance')->pluck('date')->all(),
        );

        $this->actingAs($student)
            ->get(route('student.dashboard', [
                'period' => 'custom',
                'start_date' => '2026-03-16',
                'end_date' => '2026-03-23',
            ]))
            ->assertSessionHasErrors('end_date');
    }

    public function test_student_and_admin_profiles_render_the_same_empty_custom_range_chart(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-25 12:00:00'));

        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $generalTest = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => true,
        ]);
        $this->createResult($student, $generalTest, 8, 10, CarbonImmutable::parse('2026-03-23 10:00:00'));

        $rangeQuery = [
            'period' => 'custom',
            'start_date' => '2026-03-16',
            'end_date' => '2026-03-22',
        ];
        $expectedDates = [
            'Mar 16',
            'Mar 17',
            'Mar 18',
            'Mar 19',
            'Mar 20',
            'Mar 21',
            'Mar 22',
        ];

        $studentResponse = $this->actingAs($student)
            ->get(route('student.dashboard', $rangeQuery))
            ->assertDontSee('No general-test attempts this week.')
            ->assertDontSee('No scores to display this week')
            ->assertSee('viewBox="0 0 904 330"', false)
            ->assertViewHas('studentAnalytics', [
                'attempts' => 0,
                'passing_attempts' => 0,
            ]);
        $adminResponse = $this->actingAs($admin)
            ->get(route('admin.students.show', array_merge(['student' => $student], $rangeQuery)))
            ->assertDontSee('No general-test attempts this week.')
            ->assertDontSee('No scores to display this week')
            ->assertSee('viewBox="0 0 904 330"', false)
            ->assertViewHas('studentAnalytics', [
                'attempts' => 0,
                'passing_attempts' => 0,
            ]);

        $this->assertSame(
            ['2026-03-16', '2026-03-17', '2026-03-18', '2026-03-19', '2026-03-20', '2026-03-21', '2026-03-22'],
            $studentResponse->viewData('studentPerformance')->pluck('date')->all(),
        );
        $this->assertSame(
            $studentResponse->viewData('studentPerformance')->toArray(),
            $adminResponse->viewData('studentPerformance')->toArray(),
        );

        foreach ($expectedDates as $dateLabel) {
            $studentResponse->assertSee($dateLabel);
            $adminResponse->assertSee($dateLabel);
        }
    }

    public function test_admin_student_profile_includes_course_scoped_readiness_and_study_activity(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 12:00:00'));

        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $module = Module::create(['title' => 'Database Systems', 'department_id' => $department->id, 'course_id' => $course->id]);
        $subtopic = Subtopic::create(['title' => 'SQL', 'module_id' => $module->id]);
        $generalTest = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => true,
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $generalTest->id,
            'score' => 1,
            'total_questions' => 2,
            'answers' => [
                ['question_text' => 'Question one', 'topic' => 'SQL', 'is_correct' => true],
                ['question_text' => 'Question two', 'topic' => 'SQL', 'is_correct' => false],
            ],
        ]);
        StudySession::create([
            'user_id' => $student->id,
            'module_id' => $module->id,
            'subtopic_id' => $subtopic->id,
            'activity_date' => '2026-03-20',
            'active_seconds' => 5400,
            'started_at' => '2026-03-20 10:00:00',
            'last_heartbeat_at' => '2026-03-20 11:30:00',
        ]);
        StudySession::create([
            'user_id' => $student->id,
            'module_id' => $module->id,
            'subtopic_id' => $subtopic->id,
            'activity_date' => '2026-03-01',
            'active_seconds' => 3600,
            'started_at' => '2026-03-01 10:00:00',
            'last_heartbeat_at' => '2026-03-01 11:00:00',
        ]);
        StudySession::create([
            'user_id' => $otherStudent->id,
            'module_id' => $module->id,
            'subtopic_id' => $subtopic->id,
            'activity_date' => '2026-03-20',
            'active_seconds' => 7200,
            'started_at' => '2026-03-20 10:00:00',
            'last_heartbeat_at' => '2026-03-20 12:00:00',
        ]);

        $this->assertDatabaseHas('study_sessions', [
            'user_id' => $student->id,
            'activity_date' => '2026-03-20 00:00:00',
            'active_seconds' => 5400,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.students.show', ['student' => $student, 'period' => 7]))
            ->assertSee('Board Exam Readiness')
            ->assertSee('Study Activity')
            ->assertSee('Monthly active study time')
            ->assertSee('Database Systems')
            ->assertSee('data-analytics-url="'.route('admin.students.activity.index', $student).'"', false)
            ->assertSee('data-topic-quizzes-url="'.route('admin.students.activity.topics.quizzes', ['student' => $student, 'subtopic' => '__SUBTOPIC__']).'"', false)
            ->assertSee("document.getElementById('student-activity-analytics')", false)
            ->assertSee('document.body.append(quizModalElement)', false)
            ->assertSee('id="activity-topic-quizzes-modal"', false)
            ->assertSee('id="student-board-readiness"', false)
            ->assertSee('id="readiness-gauge-progress"', false)
            ->assertSee('id="readiness-slider-list"', false)
            ->assertSee('Simulate module scores to see how they could affect the student&#039;s weighted readiness.', false)
            ->assertSee('data-readiness-slider', false)
            ->assertSee(route('admin.modules.show', $module), false)
            ->assertViewHas('readinessScore', 50)
            ->assertViewHas('readinessAssessedModuleCount', 1)
            ->assertViewHas('readinessAssessedQuestionCount', 2)
            ->assertViewHas('readinessModules', [[
                'module' => 'Database Systems',
                'score' => 50,
                'question_count' => 2,
            ]]);
    }

    public function test_admin_custom_period_filters_analytics_without_a_score_change_metric(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 12:00:00'));

        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $generalTest = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => true,
        ]);
        $this->createResult($student, $generalTest, 8, 10, CarbonImmutable::parse('2026-03-10 00:00:00'));
        $this->createResult($student, $generalTest, 8, 10, CarbonImmutable::parse('2026-03-12 15:00:00'));
        $this->createResult($student, $generalTest, 8, 10, CarbonImmutable::parse('2026-03-16 23:59:59'));
        $this->createResult($student, $generalTest, 4, 10, CarbonImmutable::parse('2026-03-05 15:00:00'));
        $this->createResult($student, $generalTest, 2, 10, CarbonImmutable::parse('2026-03-17 15:00:00'));

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard', [
                'period' => 'custom',
                'start_date' => '2026-03-10',
                'end_date' => '2026-03-16',
            ]))
            ->assertSee('Mar 10, 2026 – Mar 16, 2026')
            ->assertSee('Custom range')
            ->assertSee('analytics-period-picker')
            ->assertSee('dropdown-menu-end')
            ->assertSee('Choose reporting period')
            ->assertSee('name="start_date"', false)
            ->assertSee('name="end_date"', false)
            ->assertViewHas('periodDays', 7)
            ->assertViewHas('averageCollegeScore', 80)
            ->assertViewHas('generalTestParticipants', 1)
            ->assertViewHas('collegePassRate', 100)
            ->assertDontSee('Score Change')
            ->assertViewHas('activeStudentsCount', 1);

        $this->assertCount(7, $response->viewData('departmentPerformance'));
        $this->assertSame('2026-03-10', $response->viewData('departmentPerformance')->first()['date']);
        $this->assertSame('2026-03-16', $response->viewData('departmentPerformance')->last()['date']);
        $this->assertSame(80, $response->viewData('departmentPerformance')->first()['score']);
        $this->assertSame(80, $response->viewData('departmentPerformance')->last()['score']);
    }

    public function test_admin_custom_period_rejects_missing_reversed_future_and_oversized_ranges(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 12:00:00'));

        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['period' => 'custom']))
            ->assertSessionHasErrors(['start_date', 'end_date']);

        $this->get(route('admin.dashboard', [
            'period' => 'custom',
            'start_date' => '2026-03-10',
            'end_date' => '2026-03-09',
        ]))->assertSessionHasErrors(['start_date', 'end_date']);

        $this->get(route('admin.dashboard', [
            'period' => 'custom',
            'start_date' => '2026-03-20',
            'end_date' => '2026-03-21',
        ]))->assertSessionHasErrors('end_date');

        $this->get(route('admin.dashboard', [
            'period' => 'custom',
            'start_date' => '2025-03-19',
            'end_date' => '2026-03-20',
        ]))->assertSessionHasErrors('start_date');
    }

    public function test_admin_dashboard_chart_shows_the_full_30_and_90_day_periods(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 12:00:00'));

        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);

        foreach ([
            7 => ['Mar 14, 2026 – Mar 20, 2026', 1140],
            30 => ['Feb 19, 2026 – Mar 20, 2026', 1140],
            90 => ['Dec 21, 2025 – Mar 20, 2026', 2160],
        ] as $days => [$dateRange, $chartWidth]) {
            $response = $this->actingAs($admin)
                ->get(route('admin.dashboard', ['period' => $days]))
                ->assertSee("{$days} calendar days")
                ->assertSee($dateRange)
                ->assertSee("style=\"width: {$chartWidth}px; max-width: none;\"", false);

            $this->assertCount($days, $response->viewData('departmentPerformance'));
        }
    }

    public function test_super_admin_dashboard_reports_calendar_months_configured_colors_and_department_denominators(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 12:00:00'));

        $department = Department::create([
            'name' => 'Computing',
            'code' => 'COMP',
            'color_theme' => '#123456',
        ]);
        User::factory()->create([
            'role' => 'student',
            'department_id' => null,
        ]);
        $currentStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
        ]);
        $currentStudent->created_at = CarbonImmutable::parse('2026-01-12');
        $currentStudent->save();
        $previousYearStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
        ]);
        $previousYearStudent->created_at = CarbonImmutable::parse('2025-01-12');
        $previousYearStudent->save();
        $readingStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
        ]);
        $generalTest = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
        ]);
        $this->createResult($currentStudent, $generalTest, 4, 5, now()->subDay());
        $this->createResult($currentStudent, $generalTest, 3, 5, now()->subDays(40));
        $module = Module::create(['title' => 'Database Systems', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['title' => 'SQL', 'module_id' => $module->id]);
        StudySession::create([
            'user_id' => $readingStudent->id,
            'module_id' => $module->id,
            'subtopic_id' => $subtopic->id,
            'activity_date' => now()->toDateString(),
            'active_seconds' => 600,
            'started_at' => now()->subMinutes(10),
            'last_heartbeat_at' => now(),
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.dashboard', ['period' => 30]))
            ->assertSee('System analytics')
            ->assertSee('College performance and participation')
            ->assertSee('About active participation')
            ->assertViewHas('totalStudents', 3)
            ->assertViewHas('activeParticipationRate', 67)
            ->assertViewHas('periodLabel', 'Last 30 days');

        $this->assertSame('Apr 2025', $response->viewData('chartLabels')[0]);
        $this->assertSame('Mar 2026', $response->viewData('chartLabels')[11]);
        $this->assertSame('#123456', $response->viewData('departmentDatasets')[0]['borderColor']);
        $this->assertSame(1, $response->viewData('departmentDatasets')[0]['data'][9]);
        $this->assertSame([
            'students' => 3,
            'active_students' => 2,
            'participants' => 1,
            'average_score' => 80,
            'pass_rate' => 100,
        ], collect($response->viewData('departmentAnalytics')[0])->only([
            'students',
            'active_students',
            'participants',
            'average_score',
            'pass_rate',
        ])->all());
    }

    public function test_super_admin_can_filter_dashboard_analytics_to_one_college(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 12:00:00'));

        $selectedCollege = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherCollege = Department::create(['name' => 'Education', 'code' => 'EDU']);
        User::factory()->create(['role' => 'admin', 'department_id' => $selectedCollege->id]);
        User::factory()->create(['role' => 'admin', 'department_id' => $otherCollege->id]);
        $assessedStudent = User::factory()->create(['role' => 'student', 'department_id' => $selectedCollege->id]);
        $readingStudent = User::factory()->create(['role' => 'student', 'department_id' => $selectedCollege->id]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $otherCollege->id]);
        $selectedTest = StudentTest::create([
            'title' => 'Computing General Test',
            'department_id' => $selectedCollege->id,
            'is_general' => true,
        ]);
        $otherTest = StudentTest::create([
            'title' => 'Education General Test',
            'department_id' => $otherCollege->id,
            'is_general' => true,
        ]);
        $this->createResult($assessedStudent, $selectedTest, 4, 5, now()->subDay());
        $this->createResult($otherStudent, $otherTest, 1, 5, now()->subDay());
        $module = Module::create(['title' => 'Computing Foundations', 'department_id' => $selectedCollege->id]);
        $subtopic = Subtopic::create(['title' => 'Algorithms', 'module_id' => $module->id]);
        StudySession::create([
            'user_id' => $readingStudent->id,
            'module_id' => $module->id,
            'subtopic_id' => $subtopic->id,
            'activity_date' => '2026-03-20',
            'active_seconds' => 600,
            'started_at' => '2026-03-20 10:00:00',
            'last_heartbeat_at' => '2026-03-20 10:10:00',
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.dashboard', ['college_id' => $selectedCollege->id, 'period' => 7]))
            ->assertSee('Viewing engagement, assessment outcomes, and enrollment trends for Computing.')
            ->assertSee('id="super-admin-college-filter"', false)
            ->assertSee('name="college_id"', false)
            ->assertSee('name="period" value="7"', false)
            ->assertSee('name="college_id" value="'.$selectedCollege->id.'"', false)
            ->assertSee('<option value="'.$selectedCollege->id.'" selected>', false)
            ->assertViewHas('selectedDepartmentId', $selectedCollege->id)
            ->assertViewHas('period', '7')
            ->assertViewHas('totalDepartments', 1)
            ->assertViewHas('totalAdmins', 1)
            ->assertViewHas('totalStudents', 2)
            ->assertViewHas('departmentStudentTotal', 2)
            ->assertViewHas('activeStudentCount', 2)
            ->assertViewHas('activeParticipationRate', 100)
            ->assertViewHas('departmentAnalytics', fn ($analytics): bool => $analytics->count() === 1
                && $analytics->first()['name'] === 'Computing'
                && $analytics->first()['participants'] === 1
                && $analytics->first()['average_score'] === 80);

        $this->assertCount(1, $response->viewData('departmentDatasets'));
        $this->assertSame('Computing', $response->viewData('departmentDatasets')[0]['label']);
        $this->assertSame(2, $response->viewData('departmentDatasets')[0]['data'][11]);
    }

    public function test_super_admin_can_filter_dashboard_analytics_to_one_academic_program(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 12:00:00'));

        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $otherCourse = Course::create(['name' => 'Data Science', 'code' => 'DS', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $bankFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => null]);
        User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $otherCourse->id]);
        QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $bankFaculty->id,
            'question' => 'Question for Software Engineering?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);
        $student = User::factory()->create([
            'name' => 'Alpha Student',
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $otherStudent = User::factory()->create([
            'name' => 'Beta Student',
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $otherCourse->id,
        ]);
        $test = StudentTest::create([
            'title' => 'Software Engineering General Test',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'is_general' => true,
        ]);
        $otherTest = StudentTest::create([
            'title' => 'Data Science General Test',
            'department_id' => $department->id,
            'course_id' => $otherCourse->id,
            'is_general' => true,
        ]);
        $this->createResult($student, $test, 8, 10, now()->subDay());
        $this->createResult($otherStudent, $otherTest, 2, 10, now()->subDay());

        $response = $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.dashboard', [
                'course_id' => $course->id,
                'period' => 7,
                'student_search' => 'Alpha',
            ]))
            ->assertSee('Viewing engagement, assessment outcomes, and enrollment trends for Software Engineering.')
            ->assertSee('name="course_id"', false)
            ->assertSee('id="super-admin-program-filter"', false)
            ->assertSee('Software Engineering')
            ->assertDontSee('Beta Student')
            ->assertViewHas('selectedCourseId', $course->id)
            ->assertViewHas('selectedDepartmentId', $department->id)
            ->assertViewHas('totalAdmins', 2)
            ->assertViewHas('totalStudents', 1)
            ->assertViewHas('activeStudentCount', 1)
            ->assertViewHas('departmentStudentTotal', 1)
            ->assertViewHas('matchingStudents', fn ($students): bool => $students->count() === 1
                && $students->first()->id === $student->id)
            ->assertViewHas('departmentAnalytics', fn ($analytics): bool => $analytics->count() === 1
                && $analytics->first()['name'] === 'Software Engineering'
                && $analytics->first()['participants'] === 1
                && $analytics->first()['average_score'] === 80);

        $this->assertSame('Software Engineering', $response->viewData('departmentDatasets')[0]['label']);
        $this->assertSame(1, $response->viewData('departmentDatasets')[0]['data'][11]);

        $module = Module::create(['title' => 'Data Science', 'department_id' => $department->id, 'course_id' => $otherCourse->id]);
        $subtopic = Subtopic::create(['title' => 'Statistics', 'module_id' => $module->id]);
        StudySession::create([
            'user_id' => $otherStudent->id,
            'module_id' => $module->id,
            'subtopic_id' => $subtopic->id,
            'activity_date' => '2026-03-20',
            'active_seconds' => 600,
            'started_at' => '2026-03-20 10:00:00',
            'last_heartbeat_at' => '2026-03-20 10:10:00',
        ]);
        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.dashboard', ['course_id' => $course->id, 'period' => 7]))
            ->assertViewHas('activeStudentCount', 1);
    }

    public function test_super_admin_dashboard_rejects_an_unknown_college_filter(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.dashboard', ['college_id' => 999999]))
            ->assertSessionHasErrors('college_id');
    }

    public function test_super_admin_monitoring_shows_summary_and_links_to_assessment_tools(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $activeFaculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        User::factory()->unverified()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $this->createQuestionBankItem($course, $activeFaculty);
        $scheduledExam = StudentTest::create([
            'title' => 'Software Engineering Midterm',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $activeFaculty->id,
            'is_scheduled_exam' => true,
        ]);
        $practiceQuiz = StudentTest::create([
            'title' => 'Practice Quiz',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $activeFaculty->id,
            'is_scheduled_exam' => false,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $this->createResult($student, $scheduledExam, 8, 10, now());
        $this->createResult($student, $practiceQuiz, 9, 10, now());
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $response = $this->actingAs($superAdmin)
            ->get(route('super-admin.monitoring'))
            ->assertOk()
            ->assertSee('Institution oversight')
            ->assertSee('Total Faculty')
            ->assertSee('Active Faculty')
            ->assertSee('Total Questions Added')
            ->assertSee('Examination Submissions')
            ->assertSee('College & Program Monitoring')
            ->assertSee('monitoring-level-tabs', false)
            ->assertSee('college-monitoring-tab', false)
            ->assertSee('program-monitoring-tab', false)
            ->assertSee('Monthly Faculty Contributions')
            ->assertDontSee('Recent Faculty Activities')
            ->assertSee('id="faculty-message-form"', false)
            ->assertSee('Message Faculty')
            ->assertSee('View Questions')
            ->assertSee('faculty-contribution-sort', false)
            ->assertSee('faculty-modal-total-folder-count', false)
            ->assertSee('faculty-modal-folders', false)
            ->assertDontSee('question-folder-grid', false)
            ->assertDontSee('Folder contribution coverage')
            ->assertSee(route('super-admin.question-bank.index'))
            ->assertSee(route('super-admin.item-analysis.index'))
            ->assertViewHas('overview', [
                'total_faculty' => 2,
                'active_faculty' => 1,
                'total_questions' => 1,
                'total_exam_submissions' => 1,
            ]);

        $this->assertSame(1, $response->viewData('departments')->first()->question_bank_items_count);
    }

    public function test_super_admin_monitoring_resolves_selected_course_to_its_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $otherCourse = Course::create(['name' => 'Data Science', 'code' => 'DS', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $this->createQuestionBankItem($otherCourse, $faculty);

        $response = $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.monitoring', ['course_id' => $course->id]))
            ->assertOk()
            ->assertViewHas('selectedCourseId', $course->id)
            ->assertViewHas('selectedDepartmentId', $department->id);

        $this->assertSame(0, array_sum($response->viewData('monthlyContributions')['values']));
        $this->assertSame(1, array_sum($response->viewData('allCollegesMonthlyContributions')['values']));
    }

    public function test_super_admin_monitoring_programs_endpoint_returns_only_programs_in_selected_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $firstCourse = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $secondCourse = Course::create(['name' => 'Data Science', 'code' => 'DS', 'department_id' => $department->id]);
        $foreignCourse = Course::create(['name' => 'Teacher Education', 'code' => 'TE', 'department_id' => $otherDepartment->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $this->createQuestionBankItem($firstCourse, $faculty);
        $this->createQuestionBankItem($firstCourse, $faculty);
        $this->createQuestionBankItem($secondCourse, $faculty);
        $this->createQuestionBankItem($foreignCourse, $faculty);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->getJson(route('super-admin.monitoring.programs', $department))
            ->assertOk()
            ->assertJsonPath('college.name', 'Computing')
            ->assertJsonCount(2, 'programs')
            ->assertJsonPath('programs.0.name', 'Data Science')
            ->assertJsonPath('programs.0.question_count', 1)
            ->assertJsonPath('programs.1.name', 'Software Engineering')
            ->assertJsonPath('programs.1.question_count', 2);
    }

    public function test_program_details_include_all_folders_and_zero_contribution_faculty(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-19 12:00:00'));

        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Civil Engineering', 'code' => 'CE', 'department_id' => $department->id]);
        $otherCourse = Course::create(['name' => 'Education', 'code' => 'ED', 'department_id' => $otherDepartment->id]);
        $faculty = User::factory()->create([
            'name' => 'Avery Faculty',
            'role' => 'admin',
            'department_id' => $department->id,
        ]);
        $zeroContributionFaculty = User::factory()->create([
            'name' => 'Bree Zero',
            'role' => 'admin',
            'department_id' => $department->id,
        ]);
        $foreignFaculty = User::factory()->create([
            'name' => 'Casey Foreign',
            'role' => 'admin',
            'department_id' => $otherDepartment->id,
        ]);
        $structuralFolder = Topic::create([
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'name' => 'Structural Engineering',
            'is_folder' => true,
        ]);
        $hydraulicsFolder = Topic::create([
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'name' => 'Hydraulics',
            'is_folder' => true,
        ]);
        $emptyFolder = Topic::create([
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'name' => 'Geotechnical Engineering',
            'is_folder' => true,
        ]);
        Topic::create([
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'name' => 'Legacy non-folder topic',
            'is_folder' => false,
        ]);
        $firstQuestion = $this->createQuestionBankItem($course, $faculty, $structuralFolder);
        $this->createQuestionBankItem($course, $faculty, $structuralFolder);
        $this->createQuestionBankItem($course, null, $hydraulicsFolder);
        $this->createQuestionBankItem($otherCourse, $foreignFaculty);
        $firstQuestion->update(['question' => 'Edited without creating another contribution.']);

        $response = $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->getJson(route('super-admin.monitoring.program-details', $course))
            ->assertOk()
            ->assertJsonPath('program.question_count', 3)
            ->assertJsonPath('unlisted_creator_question_count', 1)
            ->assertJsonCount(3, 'folders')
            ->assertJsonPath('folders.0.name', 'Geotechnical Engineering')
            ->assertJsonPath('folders.0.contributor_count', 0)
            ->assertJsonPath('folders.1.question_count', 1)
            ->assertJsonPath('folders.2.contributors.0.name', $faculty->name)
            ->assertJsonPath('faculty.0.name', $faculty->name)
            ->assertJsonPath('faculty.0.question_count', 2)
            ->assertJsonPath('faculty.0.folders.0.name', 'Structural Engineering')
            ->assertJsonPath('faculty.1.name', $zeroContributionFaculty->name)
            ->assertJsonPath('faculty.1.question_count', 0);

        $this->assertSame(2, array_sum($response->json('monthly_contributions.values')));
        $this->assertArrayNotHasKey('recent_activities', $response->json());
        $this->assertStringNotContainsString($foreignFaculty->name, json_encode($response->json('faculty')));
    }

    public function test_faculty_contribution_modal_only_returns_folders_with_that_facultys_questions(): void
    {
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Civil Engineering', 'code' => 'CE', 'department_id' => $department->id]);
        $otherCourse = Course::create(['name' => 'Education', 'code' => 'ED', 'department_id' => $otherDepartment->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $otherFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $structuralFolder = Topic::create([
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'name' => 'Structural Engineering',
            'is_folder' => true,
        ]);
        $hydraulicsFolder = Topic::create([
            'course_id' => $course->id,
            'created_by' => $otherFaculty->id,
            'name' => 'Hydraulics',
            'is_folder' => true,
        ]);
        $this->createQuestionBankItem($course, $faculty, $structuralFolder);
        $this->createQuestionBankItem($course, $faculty, $structuralFolder);
        $this->createQuestionBankItem($course, $otherFaculty, $hydraulicsFolder);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->getJson(route('super-admin.monitoring.faculty-contributions', [
                'faculty' => $faculty,
                'course_id' => $course->id,
            ]))
            ->assertOk()
            ->assertJsonPath('college', 'Engineering')
            ->assertJsonPath('program', 'Civil Engineering')
            ->assertJsonPath('total_questions', 2)
            ->assertJsonPath('folder_count', 1)
            ->assertJsonPath('total_folder_count', 2)
            ->assertJsonCount(2, 'folders')
            ->assertJsonPath('folders.0.name', 'Hydraulics')
            ->assertJsonPath('folders.0.has_contribution', false)
            ->assertJsonPath('folders.1.name', 'Structural Engineering')
            ->assertJsonPath('folders.1.has_contribution', true);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->getJson(route('super-admin.monitoring.faculty-contributions', [
                'faculty' => $faculty,
                'course_id' => $otherCourse->id,
            ]))
            ->assertNotFound();
    }

    public function test_super_admin_can_send_a_one_way_message_to_a_college_faculty_member(): void
    {
        Notification::fake();
        $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $course = Course::create(['name' => 'Civil Engineering', 'code' => 'CE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $superAdmin = User::factory()->create(['role' => 'super-admin', 'name' => 'System Admin']);

        $this->actingAs($superAdmin)
            ->postJson(route('super-admin.monitoring.faculty-message', $faculty), [
                'course_id' => $course->id,
                'message' => 'Please review your folder coverage.',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Message sent to '.$faculty->name.'.');

        Notification::assertSentTo(
            $faculty,
            FacultyMonitoringMessageNotification::class,
            fn (FacultyMonitoringMessageNotification $notification, array $channels): bool => $channels === ['database']
                && $notification->senderName === 'System Admin'
                && $notification->message === 'Please review your folder coverage.'
                && $notification->courseId === $course->id,
        );
    }

    public function test_non_super_admin_cannot_access_faculty_monitoring_endpoints(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $this->actingAs(User::factory()->create(['role' => 'admin', 'department_id' => $department->id]))
            ->get(route('super-admin.monitoring'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_role_dashboard_period_filters_reject_unsupported_ranges(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id, 'course_id' => $course->id]);
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => $department->id, 'course_id' => $course->id]);
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($student)
            ->get(route('student.dashboard', ['period' => 14]))
            ->assertSessionHasErrors('period');

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['period' => 14]))
            ->assertSessionHasErrors('period');

        $this->actingAs($admin)
            ->get(route('admin.students.show', ['student' => $student, 'period' => 14]))
            ->assertSessionHasErrors('period');

        $this->actingAs($superAdmin)
            ->get(route('super-admin.dashboard', ['period' => 14]))
            ->assertSessionHasErrors('period');
    }

    public function test_super_admin_dashboard_accepts_a_custom_reporting_range(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 12:00:00'));
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.dashboard', [
                'period' => 'custom',
                'start_date' => '2026-03-10',
                'end_date' => '2026-03-20',
            ]))
            ->assertOk()
            ->assertViewHas('periodDays', 11)
            ->assertViewHas('periodLabel', 'Mar 10, 2026 - Mar 20, 2026');
    }

    public function test_super_admin_dashboard_handles_colleges_without_recent_general_test_data(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertSee('No general-test attempts in the selected period.')
            ->assertViewHas('activeParticipationRate', 0)
            ->assertViewHas('departmentAnalytics', fn ($departmentAnalytics): bool => $departmentAnalytics->first()['average_score'] === null);
    }

    private function createResult(
        User $student,
        StudentTest $test,
        int $score,
        int $totalQuestions,
        CarbonInterface $createdAt,
    ): void {
        $result = Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'answers' => [],
        ]);
        $result->created_at = $createdAt;
        $result->save();
    }

    private function createQuestionBankItem(
        Course $course,
        ?User $author,
        ?Topic $folder = null,
    ): QuestionBankItem {
        return QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $folder?->id,
            'author_id' => $author?->id,
            'question' => 'Monitoring question for '.$course->name.'?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);
    }
}
