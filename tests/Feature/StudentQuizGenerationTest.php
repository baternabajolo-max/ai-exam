<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Module;
use App\Models\Question;
use App\Models\Result;
use App\Models\Subtopic;
use App\Models\Test as StudentTest;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentQuizGenerationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_review_page_shows_a_wait_modal_for_quiz_generation(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Photosynthesis']);

        $this->actingAs($student)
            ->get(route('student.learn.review', $subtopic))
            ->assertSee('quizGeneratingModal')
            ->assertSee('Preparing your quiz')
            ->assertSee('Pausing your study timer before the AI creates questions from this subtopic')
            ->assertSee('aria-labelledby="quizGeneratingModalLabel"', false)
            ->assertSee(route('student.learn.quiz', [
                'subtopic' => $subtopic,
                'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
            ]), false)
            ->assertSee('data-question-type="multiple-choice"', false)
            ->assertSee(route('student.learn.quiz', [
                'subtopic' => $subtopic,
                'question_type' => StudentTest::QUESTION_TYPE_IDENTIFICATION,
            ]), false);
    }

    public function test_general_test_launch_uses_multiple_choice_even_when_identification_is_requested(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        Subtopic::create(['module_id' => $module->id, 'title' => 'Photosynthesis']);

        $response = $this->actingAs($student)
            ->get(route('student.tests.generate-general', ['question_type' => StudentTest::QUESTION_TYPE_IDENTIFICATION]));

        $test = StudentTest::firstOrFail();
        $response->assertRedirect(route('student.tests.show', $test));
        $this->assertSame(StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE, $test->question_type);
    }

    public function test_identification_answers_are_scored_case_insensitively_and_saved_in_uppercase(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $test = StudentTest::create([
            'title' => 'Photosynthesis Review Quiz',
            'department_id' => $department->id,
            'is_general' => false,
            'question_type' => StudentTest::QUESTION_TYPE_IDENTIFICATION,
        ]);
        $question = Question::create([
            'test_id' => $test->id,
            'question' => 'Plants use light to produce ____. ',
            'options' => [],
            'correct_answer' => 'GLUCOSE',
            'explanation' => 'Glucose stores chemical energy.',
        ]);

        $response = $this->actingAs($student)
            ->post(route('student.tests.submit', $test), [
                'answers' => [$question->id => 'glucose'],
                'question_order' => [$question->id],
            ]);

        $this->assertDatabaseHas('results', [
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 1,
            'total_questions' => 1,
        ]);
        $result = Result::where('user_id', $student->id)->firstOrFail();
        $response->assertRedirect(route('student.results.show', $result));
        $this->assertIsArray($result->answers);
        $this->assertSame('GLUCOSE', $result->answers[0]['user_answer']);
    }

    public function test_missing_reviewer_uses_a_local_topic_fallback_for_quiz_generation(): void
    {
        config([
            'ai.api_base' => 'http://127.0.0.1:1234/v1',
            'ai.api_key' => 'lm-studio',
            'ai.model' => 'llama-3.2-3b-instruct',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:1234/v1/chat/completions' => Http::response([
                'error' => ['message' => 'The model is still loading.'],
            ], 503),
        ]);

        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Photosynthesis']);

        $this->from(route('student.learn.review', $subtopic))
            ->actingAs($student)
            ->get(route('student.learn.quiz', $subtopic))
            ->assertOk();

        $this->assertDatabaseCount('tests', 1);
        $this->assertDatabaseCount('questions', 10);
    }

    public function test_general_test_page_shows_inline_error_and_retry_controls_instead_of_a_browser_alert(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $test = StudentTest::create([
            'title' => 'General Science Assessment',
            'department_id' => $department->id,
            'is_general' => true,
        ]);

        $this->actingAs($student)
            ->get(route('student.tests.show', $test))
            ->assertSee('test-generation-error')
            ->assertSee('retry-test-generation')
            ->assertSee('Try again')
            ->assertSee('Back to Learn')
            ->assertSee(route('student.learn.index'), false)
            ->assertDontSee('Error generating test:');
    }

    public function test_general_test_generation_rejects_module_titles_without_accessible_study_material(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        Subtopic::create([
            'module_id' => $module->id,
            'title' => 'Science',
            'file_path' => 'missing/reviewer.pdf',
        ]);
        $test = StudentTest::create([
            'title' => 'General Science Assessment',
            'department_id' => $department->id,
            'is_general' => true,
        ]);

        $this->actingAs($student)
            ->getJson(route('student.tests.build', $test))
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath(
                'message',
                'There is no usable study material for a general test yet. Ask your faculty member to add module descriptions or upload accessible subtopic reviewer files, then try again.',
            );

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_general_test_generates_only_multiple_choice_questions(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        Subtopic::create([
            'module_id' => $module->id,
            'title' => 'Photosynthesis',
            'description' => 'Plants use sunlight to produce glucose and oxygen.',
        ]);
        $test = StudentTest::create([
            'title' => 'General Science Assessment',
            'department_id' => $department->id,
            'is_general' => true,
            'question_type' => StudentTest::QUESTION_TYPE_MIXED,
        ]);

        $multipleChoiceQuestions = [];
        foreach (range(1, 30) as $number) {
            $options = ["Option {$number} A", "Option {$number} B", "Option {$number} C", "Option {$number} D"];
            $multipleChoiceQuestions[] = [
                'question' => "Select the correct answer for multiple-choice question {$number}.",
                'options' => $options,
                'correct_answer' => $options[0],
                'explanation' => "Multiple-choice explanation {$number}.",
                'topic' => 'Photosynthesis',
            ];
        }

        $this->mock(AiService::class, function ($mock) use ($multipleChoiceQuestions): void {
            $mock->shouldReceive('generateGeneralTest')
                ->once()
                ->withArgs(fn (string $context, int $count, string $type, array $previousQuestions, array $weakTopics): bool => $count === 30
                    && $type === StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE
                    && $previousQuestions === []
                    && $weakTopics === [])
                ->andReturn($multipleChoiceQuestions);
        });

        $this->actingAs($student)
            ->getJson(route('student.tests.build', $test))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $questions = Question::where('test_id', $test->id)->orderBy('id')->get();
        $this->assertCount(30, $questions);
        $this->assertSame(
            array_fill(0, 30, StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE),
            $questions->pluck('question_type')->all(),
        );
        $this->assertCount(4, $questions->first()->options);
        $this->assertCount(4, $questions->last()->options);
        $this->assertSame(['Option 1 A', 'Option 1 B', 'Option 1 C', 'Option 1 D'], $questions->first()->options);

        $this->actingAs($student)
            ->get(route('student.tests.show', $test))
            ->assertDontSee('TYPE YOUR ANSWER IN UPPERCASE')
            ->assertSee('sweetalert2@11.26.3/dist/sweetalert2.all.min.js')
            ->assertSee('js/confirm-actions.js')
            ->assertSee('data-confirm-title="Leave this quiz?"', false)
            ->assertDontSee('onclick="return confirm(', false)
            ->assertSee('Option 1 A');

        $this->get(route('student.learn.index'))
            ->assertSee('30 multiple-choice questions.')
            ->assertSee('future tests focus on topics')
            ->assertDontSee('randomized questions');
    }

    public function test_general_test_prioritizes_topics_with_repeated_low_scores(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        Subtopic::create([
            'module_id' => $module->id,
            'title' => 'Photosynthesis',
            'description' => 'Plants use sunlight to produce glucose and oxygen.',
        ]);

        foreach (range(1, 2) as $attempt) {
            $previousTest = StudentTest::create([
                'title' => "General Science Assessment {$attempt}",
                'department_id' => $department->id,
                'is_general' => true,
                'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
            ]);
            $questionText = "What does photosynthesis practice question {$attempt} ask?";
            Question::create([
                'test_id' => $previousTest->id,
                'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
                'question' => $questionText,
                'topic' => 'Photosynthesis',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => 'A',
            ]);
            Result::create([
                'user_id' => $student->id,
                'test_id' => $previousTest->id,
                'score' => 0,
                'total_questions' => 1,
                'answers' => [[
                    'question_text' => $questionText,
                    'topic' => 'Photosynthesis',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'A',
                    'user_answer' => 'B',
                    'is_correct' => false,
                ]],
            ]);
        }

        $test = StudentTest::create([
            'title' => 'Adaptive General Science Assessment',
            'department_id' => $department->id,
            'is_general' => true,
            'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
        ]);
        $generatedQuestions = [];
        foreach (range(1, 30) as $number) {
            $options = ["Option {$number} A", "Option {$number} B", "Option {$number} C", "Option {$number} D"];
            $generatedQuestions[] = [
                'question' => "A new photosynthesis scenario question {$number}?",
                'options' => $options,
                'correct_answer' => $options[0],
                'explanation' => 'Photosynthesis explanation.',
                'topic' => 'Photosynthesis',
            ];
        }

        $this->mock(AiService::class, function ($mock) use ($generatedQuestions): void {
            $mock->shouldReceive('generateGeneralTest')
                ->once()
                ->withArgs(fn (string $context, int $count, string $type, array $previousQuestions, array $weakTopics): bool => $count === 30
                    && $type === StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE
                    && count($previousQuestions) === 2
                    && $weakTopics === [[
                        'topic' => 'Photosynthesis',
                        'incorrect_answers' => 2,
                        'total_questions' => 2,
                        'accuracy' => 0,
                        'repeated_in_attempts' => 2,
                    ]])
                ->andReturn($generatedQuestions);
        });

        $this->actingAs($student)
            ->getJson(route('student.tests.build', $test))
            ->assertJsonPath('status', 'success');

        $questions = Question::where('test_id', $test->id)->orderBy('id')->get();
        $this->assertCount(30, $questions);
        $this->assertSame(
            array_fill(0, 30, StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE),
            $questions->pluck('question_type')->all(),
        );
    }

    public function test_general_test_waits_for_a_weak_topic_to_recur_across_attempts(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        Subtopic::create([
            'module_id' => $module->id,
            'title' => 'Photosynthesis',
            'description' => 'Plants use sunlight to produce glucose and oxygen.',
        ]);
        $baselineTest = StudentTest::create([
            'title' => 'Baseline General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
            'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
        ]);
        $questionTexts = [
            'Which process turns light into chemical energy?',
            'What does photosynthesis produce?',
        ];
        foreach ($questionTexts as $questionText) {
            Question::create([
                'test_id' => $baselineTest->id,
                'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
                'question' => $questionText,
                'topic' => 'Photosynthesis',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => 'A',
            ]);
        }
        Result::create([
            'user_id' => $student->id,
            'test_id' => $baselineTest->id,
            'score' => 1,
            'total_questions' => 2,
            'answers' => [
                [
                    'question_text' => $questionTexts[0],
                    'topic' => 'Photosynthesis',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'A',
                    'user_answer' => 'B',
                    'is_correct' => false,
                ],
                [
                    'question_text' => $questionTexts[1],
                    'topic' => 'Photosynthesis',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'A',
                    'user_answer' => 'A',
                    'is_correct' => true,
                ],
            ],
        ]);
        $reviewTest = StudentTest::create([
            'title' => 'Unrelated Review Quiz',
            'department_id' => $department->id,
            'topic' => 'Unrelated Topic',
            'is_general' => false,
        ]);
        Result::create([
            'user_id' => $student->id,
            'test_id' => $reviewTest->id,
            'score' => 0,
            'total_questions' => 2,
            'answers' => [[
                'question_text' => 'Review-only question',
                'topic' => 'Unrelated Topic',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => 'A',
                'user_answer' => 'B',
                'is_correct' => false,
            ]],
        ]);
        $nextTest = StudentTest::create([
            'title' => 'Adaptive General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
            'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
        ]);
        $generatedQuestions = [];
        foreach (range(1, 30) as $number) {
            $options = ["Option {$number} A", "Option {$number} B", "Option {$number} C", "Option {$number} D"];
            $generatedQuestions[] = [
                'question' => "New photosynthesis scenario {$number}?",
                'options' => $options,
                'correct_answer' => $options[0],
                'topic' => 'Photosynthesis',
            ];
        }

        $this->mock(AiService::class, function ($mock) use ($generatedQuestions): void {
            $mock->shouldReceive('generateGeneralTest')
                ->once()
                ->withArgs(fn (string $context, int $count, string $type, array $previousQuestions, array $weakTopics): bool => $count === 30
                    && $type === StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE
                    && count($previousQuestions) === 2
                    && $weakTopics === [])
                ->andReturn($generatedQuestions);
        });

        $this->actingAs($student)
            ->getJson(route('student.tests.build', $nextTest))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertCount(30, Question::where('test_id', $nextTest->id)->get());
        $this->assertSame(
            array_fill(0, 30, StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE),
            Question::where('test_id', $nextTest->id)->orderBy('id')->pluck('question_type')->all(),
        );
    }

    public function test_general_test_mines_only_the_latest_ten_attempts_for_recurring_weak_topics(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        Subtopic::create([
            'module_id' => $module->id,
            'title' => 'Photosynthesis',
            'description' => 'Plants use sunlight to produce glucose and oxygen.',
        ]);

        foreach (range(1, 11) as $attempt) {
            $previousTest = StudentTest::create([
                'title' => "General Science Assessment {$attempt}",
                'department_id' => $department->id,
                'is_general' => true,
                'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
            ]);
            $questionText = "Photosynthesis attempt {$attempt} question?";
            Question::create([
                'test_id' => $previousTest->id,
                'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
                'question' => $questionText,
                'topic' => 'Photosynthesis',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => 'A',
            ]);
            Result::create([
                'user_id' => $student->id,
                'test_id' => $previousTest->id,
                'score' => $attempt > 2 ? 1 : 0,
                'total_questions' => 1,
                'answers' => [[
                    'question_text' => $questionText,
                    'topic' => 'Photosynthesis',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'A',
                    'user_answer' => $attempt > 2 ? 'A' : 'B',
                    'is_correct' => $attempt > 2,
                ]],
            ]);
        }

        $test = StudentTest::create([
            'title' => 'Latest Ten General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
            'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
        ]);
        $generatedQuestions = [];
        foreach (range(1, 30) as $number) {
            $options = ["Option {$number} A", "Option {$number} B", "Option {$number} C", "Option {$number} D"];
            $generatedQuestions[] = [
                'question' => "A photosynthesis question {$number}?",
                'options' => $options,
                'correct_answer' => $options[0],
                'topic' => 'Photosynthesis',
            ];
        }

        $this->mock(AiService::class, function ($mock) use ($generatedQuestions): void {
            $mock->shouldReceive('generateGeneralTest')
                ->once()
                ->withArgs(fn (string $context, int $count, string $type, array $previousQuestions, array $weakTopics): bool => $count === 30
                    && count($previousQuestions) === 5
                    && $weakTopics === [])
                ->andReturn($generatedQuestions);
        });

        $this->actingAs($student)
            ->getJson(route('student.tests.build', $test))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertCount(30, Question::where('test_id', $test->id)->get());
    }

    public function test_general_test_generation_failure_returns_an_inline_error_without_saving_questions(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        Subtopic::create([
            'module_id' => $module->id,
            'title' => 'Photosynthesis',
            'description' => 'Plants use sunlight to produce glucose and oxygen.',
        ]);
        $test = StudentTest::create([
            'title' => 'General Science Assessment',
            'department_id' => $department->id,
            'is_general' => true,
        ]);

        $this->mock(AiService::class, function ($mock): void {
            $mock->shouldReceive('generateGeneralTest')
                ->once()
                ->andThrow(new \RuntimeException('The AI could not generate questions.'));
        });

        $response = $this->actingAs($student)
            ->getJson(route('student.tests.build', $test));

        $response->assertStatus(500)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'The AI could not generate questions.');
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_generated_review_quiz_saves_the_subtopic_used_for_generation(): void
    {
        config([
            'ai.api_base' => 'http://127.0.0.1:1234/v1',
            'ai.api_key' => 'lm-studio',
            'ai.model' => 'llama-3.2-3b-instruct',
        ]);
        Http::preventStrayRequests();
        $batchResponse = static function (int $startAt): array {
            $questions = [];

            for ($number = $startAt; $number < $startAt + 5; $number++) {
                $questions[] = [
                    'question' => "Plants use light to produce ____ in question {$number}.",
                    'correct_answer' => 'glucose',
                    'explanation' => "Explanation {$number}.",
                ];
            }

            return [
                'choices' => [
                    ['message' => ['content' => json_encode(['questions' => $questions], JSON_THROW_ON_ERROR)]],
                ],
            ];
        };
        Http::fake([
            'http://127.0.0.1:1234/v1/chat/completions' => Http::sequence()
                ->push($batchResponse(1))
                ->push($batchResponse(6)),
        ]);

        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Photosynthesis']);

        $this->actingAs($student)
            ->get(route('student.learn.quiz', ['subtopic' => $subtopic, 'question_type' => StudentTest::QUESTION_TYPE_IDENTIFICATION]))
            ->assertOk()
            ->assertSee('identification-answer')
            ->assertSee('TYPE YOUR ANSWER')
            ->assertDontSee('Untimed Assessment')
            ->assertDontSee('Untimed')
            ->assertSee('sweetalert2@11.26.3/dist/sweetalert2.all.min.js')
            ->assertSee('js/confirm-actions.js')
            ->assertSee('data-confirm-title="Leave this quiz?"', false)
            ->assertDontSee('onclick="return confirm(', false)
            ->assertViewHas('test', fn (StudentTest $test): bool => $test->subtopic_id === $subtopic->id
                && $test->question_type === StudentTest::QUESTION_TYPE_IDENTIFICATION);

        $this->assertDatabaseHas('tests', [
            'title' => 'Photosynthesis - Review Quiz',
            'subtopic_id' => $subtopic->id,
            'question_type' => StudentTest::QUESTION_TYPE_IDENTIFICATION,
        ]);
        $this->assertDatabaseCount('questions', 10);
        $quiz = StudentTest::where('subtopic_id', $subtopic->id)->firstOrFail();
        $firstQuestion = Question::where('test_id', $quiz->id)->firstOrFail();
        $this->assertSame(StudentTest::QUESTION_TYPE_IDENTIFICATION, $firstQuestion->question_type);
        $this->assertSame([], $firstQuestion->options);
        $this->assertSame('PHOTOSYNTHESIS', $firstQuestion->correct_answer);
    }

    public function test_submitting_a_review_quiz_redirects_to_its_result_without_creating_another_quiz(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Photosynthesis']);
        $test = StudentTest::create([
            'title' => 'Photosynthesis - Review Quiz',
            'topic' => 'Photosynthesis',
            'department_id' => $department->id,
            'is_general' => false,
            'subtopic_id' => $subtopic->id,
        ]);
        $question = Question::create([
            'test_id' => $test->id,
            'question' => 'What do plants use to make food?',
            'options' => ['Sunlight', 'Sand', 'Plastic', 'Metal'],
            'correct_answer' => 'Sunlight',
            'explanation' => 'Light powers photosynthesis.',
        ]);

        $this->actingAs($student)
            ->post(route('student.tests.submit', $test), [
                'answers' => [$question->id => 'Sunlight'],
                'question_order' => [$question->id],
            ])
            ->assertRedirect(route('student.results.show', 1));

        $this->assertDatabaseHas('results', [
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 1,
            'total_questions' => 1,
        ]);
        $this->assertDatabaseCount('tests', 1);

        $this->actingAs($student)
            ->get(route('student.results.show', 1))
            ->assertSee('Topic Review')
            ->assertSee('What do plants use to make food?')
            ->assertSee('Review again')
            ->assertSee(route('student.learn.review', $subtopic), false);
    }

    public function test_submitting_a_general_test_redirects_to_its_result_without_creating_another_test(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $test = StudentTest::create([
            'title' => 'General Science Assessment',
            'topic' => null,
            'department_id' => $department->id,
            'is_general' => true,
            'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
        ]);
        $firstQuestion = Question::create([
            'test_id' => $test->id,
            'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
            'question' => 'Which process allows plants to make glucose?',
            'topic' => 'Photosynthesis',
            'options' => ['Photosynthesis', 'Respiration', 'Digestion', 'Fermentation'],
            'correct_answer' => 'Photosynthesis',
            'explanation' => 'Photosynthesis stores light energy in glucose.',
        ]);
        $multipleChoiceQuestion = Question::create([
            'test_id' => $test->id,
            'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
            'question' => 'What do plants use to make food?',
            'options' => ['Sunlight', 'Sand', 'Plastic', 'Metal'],
            'correct_answer' => 'Sunlight',
            'explanation' => 'Light powers photosynthesis.',
        ]);

        $this->actingAs($student)
            ->post(route('student.tests.submit', $test), [
                'answers' => [$firstQuestion->id => 'Photosynthesis', $multipleChoiceQuestion->id => 'Sunlight'],
                'question_order' => [$firstQuestion->id, $multipleChoiceQuestion->id],
            ])
            ->assertRedirect(route('student.results.show', 1));

        $this->assertDatabaseHas('results', [
            'user_id' => $student->id,
            'test_id' => $test->id,
            'score' => 2,
            'total_questions' => 2,
        ]);
        $this->assertDatabaseCount('tests', 1);

        $this->actingAs($student)
            ->get(route('student.results.show', 1))
            ->assertSee('General Assessment')
            ->assertSee('Which process allows plants to make glucose?')
            ->assertSee('What do plants use to make food?')
            ->assertDontSee('Review again');

        $result = Result::where('user_id', $student->id)->firstOrFail();
        $this->assertSame('Photosynthesis', $result->answers[0]['user_answer']);
        $this->assertSame('Photosynthesis', $result->answers[0]['topic']);
        $this->assertSame(StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE, $result->answers[0]['question_type']);
        $this->assertSame(StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE, $result->answers[1]['question_type']);
    }

    public function test_general_test_with_legacy_identification_questions_is_regenerated_before_display(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $test = StudentTest::create([
            'title' => 'Legacy General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
            'question_type' => StudentTest::QUESTION_TYPE_MIXED,
        ]);
        Question::create([
            'test_id' => $test->id,
            'question_type' => StudentTest::QUESTION_TYPE_IDENTIFICATION,
            'question' => 'Plants use light to produce ____.',
            'options' => [],
            'correct_answer' => 'GLUCOSE',
        ]);

        $this->actingAs($student)
            ->get(route('student.tests.show', $test))
            ->assertOk()
            ->assertSee('AI is generating your general test')
            ->assertSee('The AI is generating 30 personalized questions')
            ->assertDontSee('id="answer-', false);

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseHas('tests', [
            'id' => $test->id,
            'question_type' => StudentTest::QUESTION_TYPE_MULTIPLE_CHOICE,
        ]);
    }

    public function test_general_test_submission_rejects_identification_questions(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $test = StudentTest::create([
            'title' => 'Legacy General Assessment',
            'department_id' => $department->id,
            'is_general' => true,
            'question_type' => StudentTest::QUESTION_TYPE_MIXED,
        ]);
        $question = Question::create([
            'test_id' => $test->id,
            'question_type' => StudentTest::QUESTION_TYPE_IDENTIFICATION,
            'question' => 'Plants use light to produce ____.',
            'options' => [],
            'correct_answer' => 'GLUCOSE',
        ]);

        $this->actingAs($student)
            ->post(route('student.tests.submit', $test), [
                'answers' => [$question->id => 'GLUCOSE'],
                'question_order' => [$question->id],
            ])
            ->assertStatus(409);

        $this->assertDatabaseCount('results', 0);
    }

    public function test_result_advice_identifies_focus_topics_and_repeated_answer_confusion(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Plant Biology', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(['module_id' => $module->id, 'title' => 'Photosynthesis']);
        $topicTest = StudentTest::create([
            'title' => 'Photosynthesis Quiz',
            'department_id' => $department->id,
            'subtopic_id' => $subtopic->id,
            'is_general' => false,
            'topic' => 'Photosynthesis',
        ]);
        $topicResult = Result::create([
            'user_id' => $student->id,
            'test_id' => $topicTest->id,
            'score' => 1,
            'total_questions' => 4,
            'answers' => [
                [
                    'question_text' => 'Which process converts light into chemical energy?',
                    'topic' => 'Photosynthesis',
                    'user_answer' => 'Respiration',
                    'correct_answer' => 'Photosynthesis',
                    'explanation' => 'Photosynthesis stores light energy in glucose.',
                    'is_correct' => false,
                ],
                [
                    'question_text' => 'Where does photosynthesis occur?',
                    'topic' => 'Photosynthesis',
                    'user_answer' => 'Mitochondria',
                    'correct_answer' => 'Chloroplasts',
                    'explanation' => 'Chloroplasts contain chlorophyll and are the site of photosynthesis.',
                    'is_correct' => false,
                ],
                [
                    'question_text' => 'Which organelle contains chlorophyll?',
                    'topic' => 'Photosynthesis',
                    'user_answer' => 'Mitochondria',
                    'correct_answer' => 'Chloroplasts',
                    'explanation' => 'Chloroplasts contain chlorophyll and are the site of photosynthesis.',
                    'is_correct' => false,
                ],
                [
                    'question_text' => 'What pigment absorbs light?',
                    'topic' => 'Photosynthesis',
                    'user_answer' => 'Chlorophyll',
                    'correct_answer' => 'Chlorophyll',
                    'explanation' => 'Chlorophyll absorbs light energy.',
                    'is_correct' => true,
                ],
            ],
        ]);
        $generalTest = StudentTest::create([
            'title' => 'General Science Test',
            'department_id' => $department->id,
            'is_general' => true,
        ]);
        $generalResult = Result::create([
            'user_id' => $student->id,
            'test_id' => $generalTest->id,
            'score' => 2,
            'total_questions' => 10,
            'answers' => [],
        ]);
        $masteryResult = Result::create([
            'user_id' => $student->id,
            'test_id' => $topicTest->id,
            'score' => 1,
            'total_questions' => 1,
            'answers' => [[
                'question_text' => 'Which cell structure contains genetic material?',
                'topic' => 'Cell Biology',
                'user_answer' => 'Nucleus',
                'correct_answer' => 'Nucleus',
                'explanation' => 'The nucleus stores the cell genetic material.',
                'is_correct' => true,
            ]],
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.results.show', $topicResult))
            ->assertSee('Personalized study advice')
            ->assertSee('Focus areas')
            ->assertSee('Photosynthesis')
            ->assertSee('25% accuracy')
            ->assertSee('You answered 1 of 4 correctly')
            ->assertSee('Revisit: Photosynthesis, Chloroplasts')
            ->assertSee('Pattern in your answers')
            ->assertSee('You selected')
            ->assertSee('Mitochondria')
            ->assertSee('instead of')
            ->assertSee('Three practical ways to review')
            ->assertSee('Make a quick compare-and-contrast note')
            ->assertSee('Which process converts light into chemical energy?')
            ->assertDontSee('Recommended next step')
            ->assertDontSee('Browse Learning Modules')
            ->assertSee('Return to Modules')
            ->assertSee('href="'.route('student.learn.index').'"', false);
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'Which process converts light into chemical energy?'),
        );
        $this->assertSame(1, substr_count($response->getContent(), 'Photosynthesis stores light energy in glucose.'));

        $this->get(route('student.results.show', $generalResult))
            ->assertDontSee('Personalized study advice')
            ->assertDontSee('Recommended next step')
            ->assertSee('Return to Modules');

        $this->get(route('student.results.show', $masteryResult))
            ->assertSee('Personalized study advice')
            ->assertSee('Excellent work—you answered every question correctly.')
            ->assertSee('Going well:')
            ->assertSee('Cell Biology (100%)')
            ->assertDontSee('Focus areas');
    }
}
