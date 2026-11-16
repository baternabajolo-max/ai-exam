<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Module;
use App\Models\Question;
use App\Models\Result;
use App\Models\Subtopic;
use App\Models\Test as StudentTest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentDashboardAnalyticsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_uses_only_the_latest_general_test_for_readiness(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI', 'color_theme' => '#123456']);
        $otherDepartment = Department::create(['name' => 'Arts', 'code' => 'ART']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $databaseModule = Module::create(['title' => 'Database Systems', 'department_id' => $department->id]);
        $ethicsModule = Module::create(['title' => 'Information Ethics', 'department_id' => $department->id]);
        $networkModule = Module::create(['title' => 'Network Security', 'department_id' => $department->id]);
        Module::create(['title' => 'Foreign Module', 'department_id' => $otherDepartment->id]);
        Subtopic::create(['module_id' => $databaseModule->id, 'title' => 'Database']);
        Subtopic::create(['module_id' => $ethicsModule->id, 'title' => 'Ethics']);

        $createGeneralAttempt = function (User $attemptStudent, string $title, array $questionData, string $createdAt) use ($department): void {
            $test = StudentTest::create([
                'title' => $title,
                'department_id' => $department->id,
                'is_general' => true,
            ]);
            $answers = [];
            $score = 0;

            foreach ($questionData as $index => $data) {
                $question = Question::create([
                    'test_id' => $test->id,
                    'question' => "{$title} question {$index}",
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'A',
                    'topic' => $data['topic'],
                ]);
                $answers[] = [
                    'question_text' => $question->question,
                    'topic' => $data['topic'],
                    'is_correct' => $data['correct'],
                ];
                $score += $data['correct'] ? 1 : 0;
            }

            $result = Result::create([
                'user_id' => $attemptStudent->id,
                'test_id' => $test->id,
                'score' => $score,
                'total_questions' => count($questionData),
                'answers' => $answers,
            ]);
            $result->forceFill(['created_at' => $createdAt])->save();
        };

        $createGeneralAttempt($student, 'First general test', [
            ['topic' => 'Ethics', 'correct' => true],
            ['topic' => 'Ethics', 'correct' => true],
            ['topic' => 'Database', 'correct' => true],
            ['topic' => 'Database', 'correct' => false],
            ['topic' => 'General', 'correct' => false],
        ], '2026-03-19 10:00:00');
        $createGeneralAttempt($student, 'Second general test', [
            ['topic' => 'Ethics', 'correct' => false],
            ['topic' => 'Database', 'correct' => false],
            ['topic' => 'General', 'correct' => false],
            ['topic' => 'General', 'correct' => true],
        ], '2026-03-20 10:00:00');
        $createGeneralAttempt($otherStudent, 'Other student general test', [
            ['topic' => 'Ethics', 'correct' => true],
        ], '2026-03-20 11:00:00');

        $targetedTest = StudentTest::create([
            'title' => 'Information Ethics Review Quiz',
            'topic' => 'Ethics',
            'department_id' => $department->id,
            'is_general' => false,
        ]);
        $targetedQuestion = Question::create([
            'test_id' => $targetedTest->id,
            'question' => 'Targeted quiz question',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'topic' => 'Ethics',
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $targetedTest->id,
            'score' => 1,
            'total_questions' => 1,
            'answers' => [[
                'question_text' => $targetedQuestion->question,
                'topic' => 'Ethics',
                'is_correct' => true,
            ]],
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertViewHas('readinessScore', 25)
            ->assertViewHas('readinessAttemptsCount', 1)
            ->assertViewHas('readinessAssessedQuestionCount', 4)
            ->assertViewHas('readinessAssessedModuleCount', 2)
            ->assertSee('Board Exam Readiness')
            ->assertSee('NEEDS FOCUS')
            ->assertSee('75% target')
            ->assertSee('Latest general-test result: 25% across 4 questions.')
            ->assertSee('Curriculum progress')
            ->assertSee('--student-theme: #123456', false)
            ->assertDontSee('Available Modules')
            ->assertSee('Database Systems')
            ->assertSee('Information Ethics')
            ->assertSee('Network Security')
            ->assertDontSee('System Analysis')
            ->assertDontSee('Software Engineering')
            ->assertDontSee('Foreign Module')
            ->assertSee('Reset to actual');

        $this->assertSame([
            ['module' => 'Database Systems', 'score' => 0, 'question_count' => 1],
            ['module' => 'Information Ethics', 'score' => 0, 'question_count' => 1],
            ['module' => 'Network Security', 'score' => null, 'question_count' => 0],
        ], $response->viewData('readinessModules'));
    }

    public function test_readiness_gauge_matches_the_latest_attempt_percentage_instead_of_aggregating_attempts(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        $test = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
        ]);
        $answers = collect(range(1, 30))
            ->map(fn (int $number): array => [
                'question_text' => "Question {$number}",
                'topic' => 'Science',
                'is_correct' => $number <= 4,
            ])
            ->all();

        $olderResult = Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 8,
            'total_questions' => 10,
            'answers' => array_slice($answers, 0, 10),
        ]);
        $olderResult->forceFill(['created_at' => '2026-03-19 10:00:00'])->save();
        $latestResult = Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 4,
            'total_questions' => 30,
            'answers' => $answers,
        ]);
        $latestResult->forceFill(['created_at' => '2026-03-20 10:00:00'])->save();

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertViewHas('readinessScore', 13)
            ->assertViewHas('readinessAttemptsCount', 1)
            ->assertViewHas('readinessAssessedQuestionCount', 30)
            ->assertViewHas('readinessModules', [[
                'module' => 'Science',
                'score' => 13,
                'question_count' => 30,
            ]])
            ->assertSee('Latest general-test result: 13% across 30 questions.');
    }

    public function test_dashboard_shows_no_readiness_score_before_assessment_results_exist(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Cloud Computing', 'department_id' => $department->id]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertViewHas('readinessScore', null)
            ->assertViewHas('readinessModules', [[
                'module' => $module->title,
                'score' => null,
                'question_count' => 0,
            ]])
            ->assertSee('AWAITING DATA')
            ->assertSee('No general-test score recorded yet. Adjust a module slider to preview a simulated result.')
            ->assertDontSee('No general-test attempts in this period.')
            ->assertSee('Cloud Computing')
            ->assertSee('No data')
            ->assertSee('value="50"', false)
            ->assertSee('data-readiness-slider', false)
            ->assertDontSee('Ethics')
            ->assertDontSee('System Analysis')
            ->assertDontSee('Web Tech')
            ->assertDontSee('Database')
            ->assertDontSee('Software Engineering');
    }

    public function test_dashboard_attributes_unlabeled_general_test_answers_to_the_only_available_module(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        $test = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
        ]);
        $questions = collect([
            ['question' => 'Question one', 'correct' => true],
            ['question' => 'Question two', 'correct' => false],
        ])->map(function (array $data) use ($test): Question {
            return Question::create([
                'test_id' => $test->id,
                'question' => $data['question'],
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => 'A',
                'topic' => 'General',
            ]);
        });
        Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 0,
            'total_questions' => 2,
            'answers' => $questions->map(fn (Question $question, int $index): array => [
                'question_text' => $question->question,
                'topic' => 'General',
                'is_correct' => $index === 0,
            ])->all(),
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertViewHas('readinessScore', 0)
            ->assertViewHas('readinessAssessedQuestionCount', 2)
            ->assertViewHas('readinessAssessedModuleCount', 1)
            ->assertSee('Science')
            ->assertSee('Latest general-test result: 0% across 2 questions.')
            ->assertSee('50%');

        $this->assertSame([
            ['module' => $module->title, 'score' => 50, 'question_count' => 2],
        ], $response->viewData('readinessModules'));
    }

    public function test_dashboard_uses_general_test_answer_summaries_for_module_scores(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Web Technology', 'department_id' => $department->id]);
        Subtopic::create(['module_id' => $module->id, 'title' => 'HTTP']);
        $test = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
        ]);
        $question = Question::create([
            'test_id' => $test->id,
            'question' => 'Which protocol serves web pages?',
            'options' => ['HTTP', 'FTP', 'SMTP', 'SSH'],
            'correct_answer' => 'HTTP',
            'topic' => 'HTTP',
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 0,
            'total_questions' => 1,
            'answers_summary' => [
                $question->id => [
                    'question' => $question->question,
                    'student_answer' => 'FTP',
                    'correct_answer' => 'HTTP',
                ],
            ],
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertViewHas('readinessScore', 0)
            ->assertSee('NEEDS FOCUS')
            ->assertSee('Web Technology');

        $this->assertSame([
            ['module' => 'Web Technology', 'score' => 0, 'question_count' => 1],
        ], $response->viewData('readinessModules'));
    }

    public function test_dashboard_colors_low_passing_and_high_module_scores_distinctly(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $moduleScores = [
            'Low Module' => 50,
            'Passing Module' => 75,
            'High Module' => 90,
        ];
        $test = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
        ]);
        $answers = [];
        $score = 0;
        $totalQuestions = 0;

        foreach ($moduleScores as $moduleTitle => $percentage) {
            Module::create(['title' => $moduleTitle, 'department_id' => $department->id]);

            for ($index = 0; $index < 20; $index++) {
                $isCorrect = $index < ($percentage / 5);
                Question::create([
                    'test_id' => $test->id,
                    'question' => "{$moduleTitle} question {$index}",
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'A',
                    'topic' => $moduleTitle,
                ]);
                $answers[] = [
                    'question_text' => "{$moduleTitle} question {$index}",
                    'topic' => $moduleTitle,
                    'is_correct' => $isCorrect,
                ];
                $score += $isCorrect ? 1 : 0;
                $totalQuestions++;
            }
        }

        Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'answers' => $answers,
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertSee('Low · under 75%')
            ->assertSee('Passing · 75–89%')
            ->assertSee('High · 90%+')
            ->assertSee('readiness-subject-score is-passing', false)
            ->assertSee('readiness-subject-score is-high', false)
            ->assertSee('readiness-bar-fill is-passing', false)
            ->assertSee('readiness-bar-fill is-high', false);

        $this->assertSame(72, $response->viewData('readinessScore'));
        $this->assertSame(1, $response->viewData('readinessAttemptsCount'));
        $this->assertSame(60, $response->viewData('readinessAssessedQuestionCount'));
        $this->assertSame([
            ['module' => 'High Module', 'score' => 90, 'question_count' => 20],
            ['module' => 'Low Module', 'score' => 50, 'question_count' => 20],
            ['module' => 'Passing Module', 'score' => 75, 'question_count' => 20],
        ], $response->viewData('readinessModules'));
    }

    public function test_dashboard_colors_readiness_gauge_dark_green_for_high_mastery(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        $test = StudentTest::create([
            'title' => 'General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
        ]);
        $question = Question::create([
            'test_id' => $test->id,
            'question' => 'Science question',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'topic' => $module->title,
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 1,
            'total_questions' => 1,
            'answers' => [[
                'question_text' => $question->question,
                'topic' => $module->title,
                'is_correct' => true,
            ]],
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertViewHas('readinessScore', 100)
            ->assertSee('readiness-status is-high', false)
            ->assertSee('readiness-gauge-progress is-high', false)
            ->assertSee('readiness-gauge-value is-high', false)
            ->assertSee('readiness-subject-score is-high', false)
            ->assertSee('readiness-bar-fill is-high', false)
            ->assertSee('readiness-slider is-high', false);
    }
}
