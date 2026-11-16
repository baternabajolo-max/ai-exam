<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Question;
use App\Models\Result;
use App\Models\Test as Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ItemAnalysisControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_faculty_selects_an_examination_from_a_modal(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($department->id, $course->id);

        $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index'))
            ->assertSee('data-bs-target="#selectFacultyExamModal"', false)
            ->assertSee('id="selectFacultyExamModal"', false)
            ->assertSee('name="test_id"', false)
            ->assertSee($exam->title);
    }

    public function test_faculty_item_analysis_counts_each_students_latest_response_per_question(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $firstStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $secondStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $legacyStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($department->id, $course->id);
        $firstQuestion = Question::create([
            'test_id' => $exam->id,
            'question' => 'Which protocol protects web traffic?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'HTTP'],
            'correct_answer' => 'HTTPS',
            'explanation' => 'HTTPS encrypts web traffic.',
            'topic' => 'Network security',
        ]);
        $secondQuestion = Question::create([
            'test_id' => $exam->id,
            'question' => 'Which protocol sends email?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'HTTP'],
            'correct_answer' => 'SMTP',
            'topic' => 'Email protocols',
        ]);
        $thirdQuestion = Question::create([
            'test_id' => $exam->id,
            'question' => 'Which protocol secures web traffic?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'HTTP'],
            'correct_answer' => 'HTTPS',
            'topic' => 'Network security',
        ]);

        Result::create([
            'user_id' => $firstStudent->id,
            'test_id' => $exam->id,
            'score' => 1,
            'total_questions' => 2,
            'answers' => [
                [
                    'question_id' => $firstQuestion->id,
                    'question_text' => $firstQuestion->question,
                    'options' => $firstQuestion->options,
                    'user_answer' => 'SMTP',
                    'is_correct' => false,
                ],
                ['question_id' => $secondQuestion->id, 'question_text' => $secondQuestion->question, 'is_correct' => true],
            ],
        ]);
        Result::create([
            'user_id' => $firstStudent->id,
            'test_id' => $exam->id,
            'score' => 1,
            'total_questions' => 2,
            'answers' => [
                [
                    'question_id' => $firstQuestion->id,
                    'question_text' => $firstQuestion->question,
                    'options' => $firstQuestion->options,
                    'user_answer' => 'HTTPS',
                    'is_correct' => true,
                ],
                ['question_id' => $secondQuestion->id, 'question_text' => $secondQuestion->question, 'is_correct' => false],
                ['question_id' => $thirdQuestion->id, 'question_text' => $thirdQuestion->question, 'is_correct' => true],
            ],
        ]);
        Result::create([
            'user_id' => $secondStudent->id,
            'test_id' => $exam->id,
            'score' => 0,
            'total_questions' => 2,
            'answers' => [
                [
                    'question_id' => $firstQuestion->id,
                    'question_text' => $firstQuestion->question,
                    'options' => $firstQuestion->options,
                    'user_answer' => 'B',
                    'is_correct' => false,
                ],
                ['question_id' => $secondQuestion->id, 'question_text' => $secondQuestion->question, 'is_correct' => false],
                ['question_id' => $thirdQuestion->id, 'question_text' => $thirdQuestion->question, 'is_correct' => true],
            ],
        ]);
        Result::create([
            'user_id' => $legacyStudent->id,
            'test_id' => $exam->id,
            'score' => 0,
            'total_questions' => 1,
            'answers' => [
                ['question_id' => $firstQuestion->id, 'question_text' => $firstQuestion->question, 'is_correct' => 'false'],
            ],
        ]);

        $response = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', ['test_id' => $exam->id]))
            ->assertOk()
            ->assertSee($firstQuestion->question)
            ->assertSee('Review question')
            ->assertSee('aria-label="Review question:', false)
            ->assertSee('data-bs-target="#facultyQuestionReview', false)
            ->assertSee('id="facultyQuestionReview', false)
            ->assertSee('role="img"', false)
            ->assertSee('Student answer distribution')
            ->assertSee('Unanswered')
            ->assertSee('1 response · 33%')
            ->assertSee('FTP')
            ->assertSee('Answer key')
            ->assertSee('Latest student attempts')
            ->assertSee('2 incorrect')
            ->assertSee('The highlighted choice is the answer key')
            ->assertSee('HTTPS encrypts web traffic.')
            ->assertSee('data-bs-target="#facultyQuestionReview2"', false)
            ->assertSee('id="facultyQuestionReview2"', false);

        $analysis = collect($response->viewData('analysis'))->keyBy('question');
        $this->assertSame(3, $analysis[$firstQuestion->question]['total']);
        $this->assertSame(1, $analysis[$firstQuestion->question]['correct']);
        $this->assertSame(2, $analysis[$firstQuestion->question]['incorrect']);
        $this->assertSame(67, $analysis[$firstQuestion->question]['incorrect_percentage']);
        $this->assertSame([
            ['label' => 'HTTPS', 'count' => 1, 'percentage' => 33, 'is_correct' => true],
            ['label' => 'FTP', 'count' => 1, 'percentage' => 33, 'is_correct' => false],
            ['label' => 'SMTP', 'count' => 0, 'percentage' => 0, 'is_correct' => false],
            ['label' => 'HTTP', 'count' => 0, 'percentage' => 0, 'is_correct' => false],
            ['label' => 'Unanswered', 'count' => 1, 'percentage' => 33, 'is_correct' => false],
        ], $analysis[$firstQuestion->question]['response_breakdown']);
        $this->assertSame(2, $analysis[$secondQuestion->question]['total']);
        $this->assertSame(0, $analysis[$secondQuestion->question]['correct']);
        $this->assertSame(2, $analysis[$secondQuestion->question]['incorrect']);
        $this->assertTrue($analysis[$secondQuestion->question]['is_difficult']);
        $this->assertSame(2, $analysis[$thirdQuestion->question]['correct']);
        $this->assertFalse($analysis[$thirdQuestion->question]['is_difficult']);
    }

    public function test_item_analysis_matches_historical_responses_without_question_ids_by_text(): void
    {
        [$department, $course] = $this->createProgram('Science', 'SCI', 'Biology', 'BIO');
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($department->id, $course->id);
        $question = Question::create([
            'test_id' => $exam->id,
            'question' => 'What is the cell powerhouse?',
            'options' => ['Mitochondrion', 'Nucleus', 'Ribosome', 'Golgi body'],
            'correct_answer' => 'Mitochondrion',
            'topic' => 'Cell biology',
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $exam->id,
            'score' => 1,
            'total_questions' => 1,
            'answers' => [
                ['question_text' => $question->question, 'is_correct' => true],
            ],
        ]);

        $response = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', ['test_id' => $exam->id]))
            ->assertOk();

        $analysis = $response->viewData('analysis');
        $this->assertCount(1, $analysis);
        $this->assertSame(1, $analysis[0]['total']);
        $this->assertSame(1, $analysis[0]['correct']);
    }

    public function test_faculty_cannot_analyze_another_programs_exam(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        [$otherDepartment, $otherCourse] = $this->createProgram('Education', 'EDU', 'Teaching', 'TEACH');
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($otherDepartment->id, $otherCourse->id);

        $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', ['test_id' => $exam->id]))
            ->assertNotFound();
    }

    public function test_admin_can_inspect_item_analysis_across_programs(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $exam = $this->createExam($department->id, $course->id);
        $question = Question::create([
            'test_id' => $exam->id,
            'question' => 'Which protocol protects web traffic?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'HTTP'],
            'correct_answer' => 'HTTPS',
            'explanation' => 'HTTPS encrypts web traffic.',
            'topic' => 'Network security',
        ]);
        $easyQuestion = Question::create([
            'test_id' => $exam->id,
            'question' => 'Which protocol resolves domain names?',
            'options' => ['DNS', 'FTP', 'SMTP', 'SSH'],
            'correct_answer' => 'DNS',
            'topic' => 'Network services',
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $exam->id,
            'score' => 1,
            'total_questions' => 2,
            'answers' => [
                ['question_id' => $question->id, 'question_text' => $question->question, 'is_correct' => false],
                ['question_id' => $easyQuestion->id, 'question_text' => $easyQuestion->question, 'is_correct' => true],
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.item-analysis.index', ['course_id' => $course->id, 'test_id' => $exam->id]))
            ->assertOk()
            ->assertSee('Cross-program Item Analysis')
            ->assertSee('data-bs-target="#selectAdminAnalysisModal"', false)
            ->assertSee('id="selectAdminAnalysisModal"', false)
            ->assertSee($question->question)
            ->assertSee('data-bs-target="#adminQuestionReview', false)
            ->assertSee('id="adminQuestionReview', false)
            ->assertSee('Student answer distribution')
            ->assertSee('FTP')
            ->assertSee('Answer key')
            ->assertSee('Latest student attempts')
            ->assertSee('HTTPS encrypts web traffic.')
            ->assertSee($easyQuestion->question)
            ->assertSee('Review question')
            ->assertSee('data-bs-target="#adminQuestionReview1"', false)
            ->assertSee('id="adminQuestionReview1"', false)
            ->assertSee($exam->title.' (1 result(s))')
            ->assertDontSee($course->name.' — '.$exam->title);
    }

    public function test_admin_must_choose_a_program_before_an_examination_and_only_sees_that_programs_exams(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        [$otherDepartment, $otherCourse] = $this->createProgram('Education', 'EDU', 'Teaching', 'TEACH');
        $admin = User::factory()->create(['role' => 'super-admin']);
        $exam = $this->createExam($department->id, $course->id);
        $otherExam = $this->createExam($otherDepartment->id, $otherCourse->id);
        $otherExam->update(['title' => 'Teaching Program Examination']);

        $this->actingAs($admin)
            ->get(route('super-admin.item-analysis.index'))
            ->assertSee('id="analysis-exam"', false)
            ->assertSee('id="analysis-exam" name="test_id" class="form-select" required disabled', false)
            ->assertSee('Choose a program first')
            ->assertSee('value="'.$exam->id.'" data-course-id="'.$course->id.'"', false)
            ->assertSee('value="'.$otherExam->id.'" data-course-id="'.$otherCourse->id.'"', false);

        $this->get(route('super-admin.item-analysis.index', [
            'course_id' => $course->id,
            'test_id' => $exam->id,
        ]))
            ->assertSee($exam->title)
            ->assertDontSee($otherExam->title);

        $this->get(route('super-admin.item-analysis.index', ['test_id' => $exam->id]))
            ->assertNotFound();

        $this->get(route('super-admin.item-analysis.index', [
            'course_id' => $course->id,
            'test_id' => $otherExam->id,
        ]))->assertNotFound();
    }

    /**
     * @return array{Department, Course}
     */
    private function createProgram(string $departmentName, string $departmentCode, string $courseName, string $courseCode): array
    {
        $department = Department::create(['name' => $departmentName, 'code' => $departmentCode]);
        $course = Course::create([
            'name' => $courseName,
            'code' => $courseCode,
            'department_id' => $department->id,
        ]);

        return [$department, $course];
    }

    private function createExam(int $departmentId, int $courseId): Exam
    {
        return Exam::create([
            'title' => 'Program Examination',
            'topic' => 'Assessment topic',
            'department_id' => $departmentId,
            'course_id' => $courseId,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => true,
            'available_from' => now()->subHour(),
        ]);
    }
}
