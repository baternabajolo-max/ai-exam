<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Result;
use App\Models\Test as Exam;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ItemAnalysisControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_faculty_selects_an_examination_from_a_modal(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        $otherCourse = Course::create([
            'name' => 'Computer Engineering',
            'code' => 'CPE',
            'department_id' => $department->id,
        ]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($department->id, $course->id);
        $otherExam = $this->createExam($department->id, $otherCourse->id);
        $otherExam->update(['title' => 'Computer Engineering Examination']);

        $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index'))
            ->assertSee('data-bs-target="#selectFacultyExamModal"', false)
            ->assertSee('id="selectFacultyExamModal"', false)
            ->assertSee('name="course_id"', false)
            ->assertSee('name="test_id"', false)
            ->assertSee('data-faculty-program-option', false)
            ->assertSee('Choose a program first')
            ->assertSee('data-bs-display="static"', false)
            ->assertSee('role="listbox"', false)
            ->assertSee('max-height: 11rem;', false)
            ->assertSee('overflow-y: auto;', false)
            ->assertSee('data-faculty-exam-option', false)
            ->assertSee('data-course-id="'.$course->id.'"', false)
            ->assertSee('data-course-id="'.$otherCourse->id.'"', false)
            ->assertSee($exam->title);

        $selectedResponse = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', [
                'course_id' => $course->id,
                'test_id' => $exam->id,
            ]));

        $this->assertSame($course->id, $selectedResponse->viewData('selectedCourseId'));
        $this->assertSame($exam->id, $selectedResponse->viewData('selectedTest')->id);
    }

    public function test_faculty_cannot_open_an_examination_that_does_not_belong_to_the_selected_program(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        $otherCourse = Course::create([
            'name' => 'Computer Engineering',
            'code' => 'CPE',
            'department_id' => $department->id,
        ]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $otherExam = $this->createExam($department->id, $otherCourse->id);

        $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', [
                'course_id' => $course->id,
                'test_id' => $otherExam->id,
            ]))
            ->assertNotFound();
    }

    public function test_topic_filter_only_lists_topics_from_the_selected_examination(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $networkExam = $this->createExam($department->id, $course->id);
        $networkExam->update(['title' => 'Network Examination']);
        $emailExam = $this->createExam($department->id, $course->id);
        $emailExam->update(['title' => 'Email Examination']);
        Question::create([
            'test_id' => $networkExam->id,
            'question' => 'Which protocol protects web traffic?',
            'options' => ['HTTPS', 'FTP'],
            'correct_answer' => 'HTTPS',
            'topic' => 'Network security',
        ]);
        Question::create([
            'test_id' => $emailExam->id,
            'question' => 'Which protocol sends email?',
            'options' => ['SMTP', 'FTP'],
            'correct_answer' => 'SMTP',
            'topic' => 'Email protocols',
        ]);

        $networkResponse = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', ['test_id' => $networkExam->id]))
            ->assertSee('value="Network security"', false)
            ->assertDontSee('value="Email protocols"', false);

        $this->assertSame(['Network security'], $networkResponse->viewData('topics'));

        $emailResponse = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', [
                'test_id' => $emailExam->id,
                'topic' => 'Network security',
            ]))
            ->assertSee('value="Email protocols"', false)
            ->assertDontSee('value="Network security"', false);

        $this->assertSame(['Email protocols'], $emailResponse->viewData('topics'));
        $this->assertSame('', $emailResponse->viewData('filters')['topic']);
        $this->assertCount(1, $emailResponse->viewData('analysis'));
    }

    public function test_faculty_can_filter_questions_by_difficulty_including_legacy_medium_values(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($department->id, $course->id);
        $questions = [];

        foreach ([
            'Easy question' => 'easy',
            'Medium question' => 'medium',
            'Moderate question' => 'moderate',
            'Hard question' => 'hard',
        ] as $questionText => $difficulty) {
            $questions[$difficulty][] = Question::create([
                'test_id' => $exam->id,
                'question' => $questionText,
                'options' => ['A', 'B'],
                'correct_answer' => 'A',
                'difficulty' => $difficulty,
            ]);
        }

        $response = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', [
                'test_id' => $exam->id,
                'difficulty' => 'moderate',
            ]))
            ->assertSee('id="analysis-difficulty"', false)
            ->assertSee('value="moderate" selected', false)
            ->assertSee('Medium question')
            ->assertSee('Moderate question')
            ->assertDontSee('Easy question')
            ->assertDontSee('Hard question');

        $this->assertSame('moderate', $response->viewData('filters')['difficulty']);
        $this->assertSame(
            [$questions['medium'][0]->question, $questions['moderate'][0]->question],
            array_column($response->viewData('analysis'), 'question'),
        );
    }

    public function test_item_analysis_uses_the_question_bank_folder_instead_of_a_legacy_non_folder_topic(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $topic = Topic::create([
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'name' => 'test',
        ]);
        $legacyTopic = Topic::create([
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_folder' => false,
            'name' => 'General',
        ]);
        $questionBankItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'author_id' => $faculty->id,
            'question' => 'Example question',
            'options' => ['A', 'B'],
            'correct_answer' => 'A',
            'topic' => 'test',
        ]);
        $exam = $this->createExam($department->id, $course->id);
        Question::create([
            'test_id' => $exam->id,
            'question_bank_item_id' => $questionBankItem->id,
            'topic_id' => $legacyTopic->id,
            'question' => 'Example question',
            'options' => ['A', 'B'],
            'correct_answer' => 'A',
            'topic' => 'General',
        ]);

        $response = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', ['test_id' => $exam->id]))
            ->assertSee('value="test"', false)
            ->assertSee('>test</td>', false)
            ->assertSee('topic_id='.$topic->id, false)
            ->assertDontSee('value="General"', false);

        $this->assertSame('test', $response->viewData('analysis')[0]['topic']);
        $this->assertSame($topic->id, $response->viewData('analysis')[0]['topic_id']);
        $this->assertSame(['test'], $response->viewData('topics'));
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
            'name' => 'Student One',
            'role' => 'student',
            'status' => 'approved',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $secondStudent = User::factory()->create([
            'name' => 'Student Two',
            'role' => 'student',
            'status' => 'approved',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $legacyStudent = User::factory()->create([
            'name' => 'Student Legacy',
            'role' => 'student',
            'status' => 'approved',
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
            ->assertSee('Status: Active · 4 result(s)')
            ->assertSee($firstQuestion->question)
            ->assertSee('Review question')
            ->assertSee('aria-label="Review question:', false)
            ->assertSee('data-bs-target="#facultyQuestionReview', false)
            ->assertSee('id="facultyQuestionReview', false)
            ->assertSee('role="img"', false)
            ->assertSee('bi-funnel', false)
            ->assertSee('data-bs-target="#analysisFiltersModal"', false)
            ->assertSee('id="analysisFiltersModal"', false)
            ->assertSee('aria-label="Open question filters"', false)
            ->assertDontSee('Search questions')
            ->assertSee('id="analysis-difficulty"', false)
            ->assertSee('Most correct')
            ->assertSee('Most incorrect')
            ->assertSee('Apply Filter')
            ->assertSee('Clear filters')
            ->assertSee('Student answer distribution')
            ->assertSee('Unanswered')
            ->assertSee('1 response · 33%')
            ->assertSee('modal-xl modal-dialog-centered modal-dialog-scrollable faculty-analysis__question-dialog', false)
            ->assertSee('max-height: min(24rem, 45dvh);', false)
            ->assertSee('Individual student responses" tabindex="0"', false)
            ->assertSee('data-faculty-answer-filter="HTTPS"', false)
            ->assertSee('data-faculty-result-filter="correct"', false)
            ->assertSee('data-faculty-result-filter="incorrect"', false)
            ->assertSee('data-student-result="correct"', false)
            ->assertSee('data-student-result="incorrect"', false)
            ->assertSee('data-student-result="unanswered"', false)
            ->assertSee('faculty-analysis__response-filter-cards', false)
            ->assertSee('faculty-analysis__response-filter-count', false)
            ->assertSee('Filter student responses by result')
            ->assertSee('FTP')
            ->assertSee('Answer key')
            ->assertSee('Latest student attempts')
            ->assertSee('2 incorrect')
            ->assertSee('The highlighted choice is the answer key')
            ->assertSee('HTTPS encrypts web traffic.')
            ->assertSee('Individual student responses')
            ->assertSee('Student One')
            ->assertSee('Student Two')
            ->assertSee('Student Legacy')
            ->assertSee('Export CSV')
            ->assertSee('Most incorrect')
            ->assertSee('data-bs-target="#facultyQuestionReview2"', false)
            ->assertSee('id="facultyQuestionReview2"', false);

        $analysis = collect($response->viewData('analysis'))->keyBy('question');
        $summary = $response->viewData('summary');
        $this->assertSame(3, $summary['participants']);
        $this->assertSame(3, $summary['eligible_students']);
        $this->assertSame(100, $summary['completion_percentage']);
        $this->assertSame(17, $summary['average_score_percentage']);
        $this->assertSame(2, $summary['questions_to_review']);
        $this->assertSame(3, $analysis[$firstQuestion->question]['total']);
        $this->assertSame(1, $analysis[$firstQuestion->question]['correct']);
        $this->assertSame(2, $analysis[$firstQuestion->question]['incorrect']);
        $this->assertSame(67, $analysis[$firstQuestion->question]['incorrect_percentage']);
        $this->assertSame(
            [
                ['student_name' => 'Student Legacy', 'answer' => 'Unanswered', 'is_correct' => false],
                ['student_name' => 'Student One', 'answer' => 'HTTPS', 'is_correct' => true],
                ['student_name' => 'Student Two', 'answer' => 'FTP', 'is_correct' => false],
            ],
            $analysis[$firstQuestion->question]['student_responses'],
        );
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

        $mostCorrectResponse = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', [
                'test_id' => $exam->id,
                'sort' => 'most-correct',
            ]));
        $this->assertSame(
            [$thirdQuestion->question, $firstQuestion->question, $secondQuestion->question],
            array_column($mostCorrectResponse->viewData('analysis'), 'question'),
        );

        $mostIncorrectResponse = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', [
                'test_id' => $exam->id,
                'sort' => 'most-incorrect',
            ]));
        $this->assertSame(
            [$secondQuestion->question, $firstQuestion->question, $thirdQuestion->question],
            array_column($mostIncorrectResponse->viewData('analysis'), 'question'),
        );

        $filteredResponse = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', [
                'test_id' => $exam->id,
                'review' => 'needs-review',
                'sort' => 'most-missed',
            ]));

        $this->assertSame(
            [$secondQuestion->question, $firstQuestion->question],
            array_column($filteredResponse->viewData('analysis'), 'question'),
        );

        $topicFilteredResponse = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', [
                'test_id' => $exam->id,
                'topic' => 'Network security',
            ]));

        $this->assertSame(
            [$firstQuestion->question, $thirdQuestion->question],
            array_column($topicFilteredResponse->viewData('analysis'), 'question'),
        );
    }

    public function test_question_review_shows_waiting_state_before_any_students_submit(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $exam = $this->createExam($department->id, $course->id);
        $question = Question::create([
            'test_id' => $exam->id,
            'question' => 'Which of these is a fruit?',
            'options' => ['Apple', 'Carrot', 'Potato', 'Celery'],
            'correct_answer' => 'Apple',
            'explanation' => 'Apples grow from the flower of an apple tree.',
        ]);

        $facultyResponse = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.index', ['test_id' => $exam->id]));
        $facultyResponse
            ->assertSee($question->question)
            ->assertSee('No student responses yet')
            ->assertSee('Answer counts and individual student records will appear here once students answer this question.')
            ->assertSee('Answer key:')
            ->assertSee('Apples grow from the flower of an apple tree.')
            ->assertDontSee('Latest student attempts')
            ->assertDontSee('Individual student responses');

        $this->actingAs($superAdmin)
            ->get(route('super-admin.item-analysis.index', [
                'course_id' => $course->id,
                'test_id' => $exam->id,
            ]))
            ->assertSee($question->question)
            ->assertSee('No student responses yet')
            ->assertSee('Answer counts and distribution will appear here once students answer this question.')
            ->assertSee('Answer key:')
            ->assertSee('Apples grow from the flower of an apple tree.')
            ->assertDontSee('Latest student attempts');
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

        $this->actingAs($faculty)
            ->get(route('admin.item-analysis.export', ['test_id' => $exam->id]))
            ->assertNotFound();
    }

    public function test_faculty_can_export_a_safe_question_level_csv_for_their_exam(): void
    {
        [$department, $course] = $this->createProgram('Computing', 'COMP', 'Software Engineering', 'SE');
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $student = User::factory()->create([
            'name' => 'Student Export',
            'role' => 'student',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $exam = $this->createExam($department->id, $course->id);
        $question = Question::create([
            'test_id' => $exam->id,
            'question' => '=HYPERLINK("https://example.com")',
            'options' => ['HTTPS', 'FTP'],
            'correct_answer' => 'HTTPS',
            'topic' => 'Network security',
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $exam->id,
            'score' => 0,
            'total_questions' => 1,
            'answers' => [[
                'question_id' => $question->id,
                'question_text' => $question->question,
                'options' => $question->options,
                'correct_answer' => $question->correct_answer,
                'user_answer' => 'FTP',
                'is_correct' => false,
            ]],
        ]);

        $response = $this->actingAs($faculty)
            ->get(route('admin.item-analysis.export', ['test_id' => $exam->id]));

        $response->assertStreamed()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
        $this->assertStringContainsString('Network security', $response->streamedContent());
        $this->assertStringContainsString('Needs review', $response->streamedContent());
        $this->assertStringNotContainsString('Student Export', $response->streamedContent());
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
