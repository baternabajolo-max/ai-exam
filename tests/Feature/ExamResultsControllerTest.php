<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Result;
use App\Models\Test as Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExamResultsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_faculty_sees_all_college_programs_and_ranked_results_for_the_selected_exam(): void
    {
        [$college, $program] = $this->createProgram('College of Science', 'SCI', 'Biology', 'BIO');
        [, $otherProgram] = $this->createProgram('College of Education', 'EDU', 'Teaching', 'TEACH');
        $unassignedProgram = Course::create([
            'name' => 'Bsce',
            'code' => 'BSCE46',
            'department_id' => $college->id,
        ]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $college->id]);
        $faculty->handledCourses()->attach($program);
        $exam = $this->createExam($college, $program, 'Biology Mock Examination');
        $otherExam = $this->createExam($college, $otherProgram, 'Teaching Mock Examination');
        $firstStudent = User::factory()->create([
            'name' => 'Avery Student',
            'role' => 'student',
            'department_id' => $college->id,
            'course_id' => $program->id,
        ]);
        $secondStudent = User::factory()->create([
            'name' => 'Jordan Student',
            'role' => 'student',
            'department_id' => $college->id,
            'course_id' => $program->id,
        ]);
        User::factory()->create([
            'name' => 'Taylor Not Taken',
            'role' => 'student',
            'department_id' => $college->id,
            'course_id' => $program->id,
        ]);

        $firstResult = $this->createResult($exam, $firstStudent, 9, 10, false, 120, 'manual');
        $secondResult = $this->createResult($exam, $secondStudent, 8, 10, true, 180, 'time_expired');

        $response = $this->actingAs($faculty)
            ->get(route('faculty.results.index', [
                'course_id' => $program->id,
                'test_id' => $exam->id,
            ]))
            ->assertOk()
            ->assertSee('Results')
            ->assertSee('href="'.route('faculty.results.index').'"', false)
            ->assertSee('id="admin-nav-student-search"', false)
            ->assertSee('data-suggestions-url="'.route('admin.students.search-suggestions').'"', false)
            ->assertDontSee('Load examinations')
            ->assertSee('value="'.$unassignedProgram->id.'"', false)
            ->assertSee('Bsce')
            ->assertDontSee('BSCE46')
            ->assertSee($exam->title)
            ->assertSee('About participation rate')
            ->assertSee('About class average score')
            ->assertSee('View Performance')
            ->assertSee('No attempt recorded')
            ->assertSee('Time Expired')
            ->assertDontSee($otherExam->title)
            ->assertDontSee('Student ID')
            ->assertDontSee('Batch')
            ->assertDontSee('student ID');

        $this->assertSame([
            'student_count' => 3,
            'participant_count' => 2,
            'participation_percentage' => 67,
            'average_percentage' => 85.0,
            'passing_count' => 1,
            'timeout_count' => 1,
        ], $response->viewData('summary'));

        $rows = $response->viewData('studentResults');
        $this->assertSame($firstStudent->id, $rows[0]['student']->id);
        $this->assertSame($firstResult->id, $rows[0]['attempt']->id);
        $this->assertSame(1, $rows[0]['rank']);
        $this->assertSame($secondStudent->id, $rows[1]['student']->id);
        $this->assertSame($secondResult->id, $rows[1]['attempt']->id);
        $this->assertSame(2, $rows[1]['rank']);
        $this->assertNull($rows[2]['attempt']);
        $this->assertNull($rows[2]['rank']);
        $response->assertSee(route('faculty.results.student', [
            'exam' => $exam->id,
            'student' => $firstStudent->id,
            'course_id' => $program->id,
            'test_id' => $exam->id,
        ]));

        $this->actingAs($faculty)
            ->get(route('faculty.results.index', [
                'course_id' => $program->id,
                'test_id' => $exam->id,
                'status' => 'not_taken',
            ]))
            ->assertSee('Taylor Not Taken')
            ->assertDontSee('Avery Student');
    }

    public function test_faculty_can_open_unassigned_programs_in_their_college_but_not_other_colleges(): void
    {
        [$college, $program] = $this->createProgram('College of Science', 'SCI', 'Biology', 'BIO');
        [$otherCollege, $otherProgram] = $this->createProgram('College of Education', 'EDU', 'Teaching', 'TEACH');
        $unassignedProgram = Course::create([
            'name' => 'Chemistry',
            'code' => 'CHEM',
            'department_id' => $college->id,
        ]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $college->id]);
        $faculty->handledCourses()->attach($program);
        $otherExam = $this->createExam($otherCollege, $otherProgram, 'Foreign Mock Examination');
        $unassignedExam = $this->createExam($college, $unassignedProgram, 'Chemistry Mock Examination');
        $unassignedStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $college->id,
            'course_id' => $unassignedProgram->id,
        ]);
        $this->createResult($unassignedExam, $unassignedStudent, 7, 10, false, 120, 'manual');
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $otherCollege->id,
            'course_id' => $otherProgram->id,
        ]);
        $this->createResult($otherExam, $student, 7, 10, false, 120, 'manual');

        $this->actingAs($faculty)
            ->get(route('faculty.results.index', [
                'course_id' => $unassignedProgram->id,
                'test_id' => $unassignedExam->id,
            ]))
            ->assertOk()
            ->assertSee($unassignedExam->title)
            ->assertSee($unassignedStudent->name);

        $this->get(route('faculty.results.student', [
            'exam' => $unassignedExam->id,
            'student' => $unassignedStudent->id,
        ]))->assertOk();

        $this->get(route('faculty.results.index', ['course_id' => $otherProgram->id]))
            ->assertNotFound();

        $this->get(route('faculty.results.student', [
            'exam' => $otherExam->id,
            'student' => $student->id,
        ]))->assertNotFound();

        $this->get(route('faculty.results.index', ['test_id' => $otherExam->id]))
            ->assertNotFound();
    }

    public function test_admin_must_choose_a_college_then_a_program_before_an_examination(): void
    {
        [$college, $program] = $this->createProgram('College of Science', 'SCI', 'Biology', 'BIO');
        [$otherCollege, $otherProgram] = $this->createProgram('College of Education', 'EDU', 'Teaching', 'TEACH');
        $admin = User::factory()->create(['role' => 'super-admin']);
        $exam = $this->createExam($college, $program, 'Biology Mock Examination');
        $otherExam = $this->createExam($otherCollege, $otherProgram, 'Teaching Mock Examination');

        $initialResultsPage = $this->actingAs($admin)
            ->get(route('super-admin.results.index'))
            ->assertOk()
            ->assertSee('href="'.route('super-admin.results.index').'"', false)
            ->assertSee('id="admin-nav-student-search"', false)
            ->assertSee('data-suggestions-url="'.route('super-admin.students.search-suggestions').'"', false)
            ->assertSee('id="results-program"', false)
            ->assertSee('id="results-exam"', false)
            ->assertDontSee('Load examinations')
            ->assertSee('Select a college first')
            ->assertDontSee($exam->title);
        $this->assertSame(
            1,
            preg_match('/<select(?=[^>]*id="results-program")(?=[^>]*\bdisabled\b)/', $initialResultsPage->getContent()),
        );

        $this->get(route('super-admin.results.index', ['college_id' => $college->id]))
            ->assertOk()
            ->assertSee('value="'.$program->id.'"', false)
            ->assertSee('data-college-id="'.$college->id.'"', false)
            ->assertDontSee($otherExam->title);

        $this->get(route('super-admin.results.index', [
            'college_id' => $college->id,
            'course_id' => $program->id,
            'test_id' => $exam->id,
        ]))
            ->assertOk()
            ->assertSee($exam->title)
            ->assertDontSee($otherExam->title);

        $this->get(route('super-admin.results.index', [
            'college_id' => $college->id,
            'course_id' => $otherProgram->id,
        ]))->assertNotFound();

        $this->get(route('super-admin.results.index', ['test_id' => $exam->id]))
            ->assertNotFound();
    }

    public function test_student_performance_uses_the_attempt_snapshot_for_topics_difficulty_and_audit_filters(): void
    {
        [$college, $program] = $this->createProgram('College of Science', 'SCI', 'Biology', 'BIO');
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $college->id]);
        $faculty->handledCourses()->attach($program);
        $student = User::factory()->create([
            'name' => 'Morgan Student',
            'role' => 'student',
            'department_id' => $college->id,
            'course_id' => $program->id,
        ]);
        $exam = $this->createExam($college, $program, 'Biology Mock Examination');
        $exam->update(['time_limit' => 60]);

        $easyQuestion = $exam->questions()->create([
            'question' => 'Easy question in Genetics?',
            'options' => ['Correct', 'Incorrect'],
            'correct_answer' => 'Correct',
            'explanation' => 'The first answer is correct.',
            'topic' => 'Genetics',
            'difficulty' => 'easy',
        ]);
        $moderateQuestion = $exam->questions()->create([
            'question' => 'Moderate question in Ecology?',
            'options' => ['Correct', 'Incorrect'],
            'correct_answer' => 'Correct',
            'explanation' => 'Ecological explanation.',
            'topic' => 'Ecology',
            'difficulty' => 'medium',
        ]);
        $hardQuestion = $exam->questions()->create([
            'question' => 'Hard question in Genetics?',
            'options' => ['Correct', 'Incorrect'],
            'correct_answer' => 'Hard answer key',
            'explanation' => 'Hard genetics explanation.',
            'topic' => 'Genetics',
            'difficulty' => 'hard',
        ]);
        $result = Result::create([
            'user_id' => $student->id,
            'test_id' => $exam->id,
            'score' => 1,
            'total_questions' => 3,
            'passed' => false,
            'started_at' => now()->subMinutes(20),
            'time_taken_seconds' => 1200,
            'submission_type' => 'time_expired',
            'answers' => [
                [
                    'question_id' => $easyQuestion->id,
                    'question_text' => 'Snapshot: Genetics question one',
                    'topic' => 'Genetics',
                    'difficulty' => 'easy',
                    'user_answer' => 'Correct',
                    'correct_answer' => 'Correct',
                    'is_correct' => true,
                    'explanation' => 'Snapshot rationale one.',
                ],
                [
                    'question_id' => $moderateQuestion->id,
                    'question_text' => 'Snapshot: Ecology question',
                    'topic' => 'Ecology',
                    'difficulty' => 'medium',
                    'user_answer' => 'Incorrect',
                    'correct_answer' => 'Correct',
                    'is_correct' => false,
                    'explanation' => 'Snapshot rationale two.',
                ],
                [
                    'question_id' => $hardQuestion->id,
                    'question_text' => 'Snapshot: Genetics question two',
                    'topic' => 'Genetics',
                    'difficulty' => 'hard',
                    'user_answer' => null,
                    'is_correct' => false,
                    'explanation' => 'Snapshot rationale three.',
                ],
            ],
        ]);

        $response = $this->actingAs($faculty)
            ->get(route('faculty.results.student', [
                'exam' => $exam->id,
                'student' => $student->id,
                'course_id' => $program->id,
                'test_id' => $exam->id,
            ]))
            ->assertOk()
            ->assertSee('Back to Results')
            ->assertSee('Auto-Submitted: Time Expired')
            ->assertSee('Snapshot: Genetics question one')
            ->assertSee('Snapshot rationale one.')
            ->assertSee('Hard answer key')
            ->assertSee('About time used')
            ->assertSee('bi-funnel', false)
            ->assertSee('audit-topic')
            ->assertSee('audit-difficulty');

        $this->assertSame(1, $response->viewData('correctCount'));
        $this->assertSame(3, $response->viewData('totalItems'));
        $this->assertSame(33.3, $response->viewData('scorePercentage'));

        $difficultyStats = collect($response->viewData('difficultyStats'))->keyBy('key');
        $this->assertSame(1, $difficultyStats['easy']['correct']);
        $this->assertSame(1, $difficultyStats['moderate']['total']);
        $this->assertSame(0, $difficultyStats['moderate']['correct']);
        $this->assertSame(1, $difficultyStats['hard']['total']);

        $topicStats = collect($response->viewData('topicStats'))->keyBy('topic');
        $this->assertSame(50.0, $topicStats['Genetics']['accuracy']);
        $this->assertSame('Weak Area', $topicStats['Genetics']['status']);
        $this->assertSame(0.0, $topicStats['Ecology']['accuracy']);
        $this->assertSame('Ecology', $response->viewData('weakestTopic')['topic']);
        $this->assertSame(1, $response->viewData('unansweredCount'));
        $this->assertSame(400, $response->viewData('averagePaceSeconds'));
        $this->assertSame(1200, $response->viewData('targetPaceSeconds'));

        $this->get(route('faculty.results.student', [
            'exam' => $exam->id,
            'student' => $student->id,
            'course_id' => $program->id,
            'item_filter' => 'unanswered',
            'topic' => 'Genetics',
            'difficulty' => 'hard',
        ]))
            ->assertSee('Snapshot: Genetics question two')
            ->assertSee('No Answer')
            ->assertSee('Snapshot rationale three.')
            ->assertDontSee('Snapshot: Ecology question');
    }

    public function test_admin_can_open_a_student_attempt_only_within_the_selected_exam_program(): void
    {
        [$college, $program] = $this->createProgram('College of Science', 'SCI', 'Biology', 'BIO');
        [$otherCollege, $otherProgram] = $this->createProgram('College of Education', 'EDU', 'Teaching', 'TEACH');
        $admin = User::factory()->create(['role' => 'super-admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $college->id,
            'course_id' => $program->id,
        ]);
        $exam = $this->createExam($college, $program, 'Biology Mock Examination');
        $otherExam = $this->createExam($otherCollege, $otherProgram, 'Teaching Mock Examination');
        $this->createResult($exam, $student, 8, 10, false, 900, 'manual');

        $this->actingAs($admin)
            ->get(route('super-admin.results.student', [
                'exam' => $exam->id,
                'student' => $student->id,
                'college_id' => $college->id,
                'course_id' => $program->id,
            ]))
            ->assertOk()
            ->assertSee($exam->title);

        $this->get(route('super-admin.results.student', [
            'exam' => $otherExam->id,
            'student' => $student->id,
            'college_id' => $otherCollege->id,
            'course_id' => $otherProgram->id,
        ]))->assertNotFound();
    }

    /**
     * @return array{Department, Course}
     */
    private function createProgram(string $collegeName, string $collegeCode, string $programName, string $programCode): array
    {
        $college = Department::create(['name' => $collegeName, 'code' => $collegeCode]);
        $program = Course::create([
            'name' => $programName,
            'code' => $programCode,
            'department_id' => $college->id,
        ]);

        return [$college, $program];
    }

    private function createExam(Department $college, Course $program, string $title): Exam
    {
        return Exam::create([
            'title' => $title,
            'topic' => $program->name,
            'department_id' => $college->id,
            'course_id' => $program->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'approval_status' => Exam::APPROVAL_STATUS_APPROVED,
            'available_from' => now()->subHour(),
            'time_limit' => 60,
        ]);
    }

    private function createResult(
        Exam $exam,
        User $student,
        int $score,
        int $totalQuestions,
        bool $passed,
        int $timeTakenSeconds,
        string $submissionType,
    ): Result {
        return Result::create([
            'user_id' => $student->id,
            'test_id' => $exam->id,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'passed' => $passed,
            'time_taken_seconds' => $timeTakenSeconds,
            'submission_type' => $submissionType,
            'answers' => [],
        ]);
    }
}
