<?php

namespace Tests\Feature\Services;

use App\Services\AiService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    public function test_math_subtopic_quiz_uses_structured_generation_with_the_extracted_formula(): void
    {
        $this->fakeLocalAiWithQuestions([
            $this->generalQuestion('How does the formula $E = mc^2$ relate mass and energy?', 'Physics'),
        ]);
        $reviewerContent = 'Einstein\'s equation is $E = mc^2$ and relates mass to energy.';

        $questions = (new AiService)->generateSubtopicQuiz('Physics', $reviewerContent, 1);

        $this->assertCount(1, $questions);
        $this->assertSame('Physics', $questions[0]['topic']);
        Http::assertSent(fn (ClientRequest $request): bool => str_contains(
            $request['messages'][0]['content'],
            'Recognize Markdown LaTeX',
        ) && str_contains($request['messages'][1]['content'], '$E = mc^2$'));
    }

    public function test_general_test_preserves_latex_from_extracted_reviewer_content(): void
    {
        $this->fakeLocalAiWithQuestions([
            $this->generalQuestion('How is $$E = mc^2$$ used?', 'Energy'),
        ]);

        $questions = (new AiService)->generateGeneralTest(
            'Module: Physics'."\n".'Subtopic: Energy'."\n".'The formula $$E = mc^2$$ relates mass and energy.',
            1,
        );

        $this->assertCount(1, $questions);
        Http::assertSent(fn (ClientRequest $request): bool => str_contains(
            $request['messages'][1]['content'],
            '$$E = mc^2$$',
        ));
    }

    public function test_generates_local_definition_questions_with_ai_rephrased_stems(): void
    {
        $this->fakeLocalAiWithStems(30, 'Rephrased question stem.');

        $questions = (new AiService)->generateSubtopicQuiz(
            'Databases',
            'Primary key is a field that uniquely identifies records. Foreign key refers to a field that links tables. '
            .'Index is a structure that improves lookup speed. Normalization means organizing data to reduce redundancy.',
            4,
        );

        $this->assertCount(4, $questions);
        foreach ($questions as $question) {
            $this->assertCount(4, $question['options']);
            $this->assertContains($question['correct_answer'], $question['options']);
            $this->assertNotSame('', $question['explanation']);
            $this->assertSame('Databases', $question['topic']);
            $this->assertSame('Rephrased question stem.', $question['question']);
        }

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === $this->chatCompletionsUrl()
            && $request['temperature'] === 0.7
            && $request['max_tokens'] === 600);
    }

    public function test_general_test_limits_generated_questions_to_weak_topics_when_possible(): void
    {
        $this->fakeLocalAiWithQuestions([
            $this->generalQuestion('A new relationship scenario asks which key links the tables?', 'Foreign key', 'application'),
            $this->generalQuestion('Which key should be used to connect these records?', 'Foreign key', 'concept'),
        ]);

        $questions = (new AiService)->generateGeneralTest(
            "Module: Databases\nPrimary key is a field that uniquely identifies records.\nForeign key refers to a field that links tables.",
            2,
            'multiple_choice',
            [],
            [['topic' => 'Foreign key', 'incorrect_answers' => 2, 'total_questions' => 3, 'accuracy' => 33]],
        );

        $this->assertNotEmpty($questions);
        foreach ($questions as $question) {
            $this->assertContains($question['topic'] ?? null, ['Foreign key']);
        }
        $this->assertSame('A new relationship scenario asks which key links the tables?', $questions[0]['question']);
        $this->assertCount(4, $questions[0]['options']);
        $this->assertContains($questions[0]['correct_answer'], $questions[0]['options']);
        Http::assertSent(fn (ClientRequest $request): bool => str_contains(
            $request['messages'][0]['content'],
            'real-world application, problem-solving/calculation, and interpretation',
        ) && str_contains($request['messages'][1]['content'], 'Foreign key'));
    }

    public function test_general_test_keeps_the_weak_subtopic_pdf_content_for_generation(): void
    {
        $this->fakeLocalAiWithQuestions([
            $this->generalQuestion('Which algorithm best fits the scheduling case?', 'Algorithms', 'application'),
        ]);

        (new AiService)->generateGeneralTest(
            "Module: Computer Science\nSubtopic: Algorithms\nDescription: Methods for solving computational problems.\n"
            ."A real-world scheduling scenario can compare algorithms by the number of operations required.\n"
            ."Module: Biology\nSubtopic: Cells\nCell membranes regulate what enters and leaves the cell.",
            1,
            weakTopics: [['topic' => 'Algorithms', 'repeated_in_attempts' => 2, 'accuracy' => 40]],
        );

        Http::assertSent(fn (ClientRequest $request): bool => str_contains(
            $request['messages'][1]['content'],
            'A real-world scheduling scenario can compare algorithms',
        ));
    }

    public function test_general_test_falls_back_when_the_structured_response_repeats_question_wording(): void
    {
        $duplicateQuestion = $this->generalQuestion('Which database concept connects these tables?', 'Databases');
        $this->fakeLocalAiWithQuestions([$duplicateQuestion, $duplicateQuestion]);

        $questions = (new AiService)->generateGeneralTest(
            "Module: Databases\nPrimary key is a field that uniquely identifies records. "
            .'Foreign key refers to a field that links tables. Index is a structure that improves lookup speed.',
            2,
        );

        $this->assertCount(2, $questions);
        $this->assertNotSame($duplicateQuestion['question'], $questions[0]['question']);
        $this->assertNotSame($questions[0]['question'], $questions[1]['question']);
    }

    public function test_general_test_falls_back_when_a_question_repeats_recent_wording(): void
    {
        $recentQuestion = $this->generalQuestion('Which database concept connects these tables?', 'Databases');
        $this->fakeLocalAiWithQuestions([
            $recentQuestion,
            $this->generalQuestion('Which database structure improves lookup speed?', 'Databases'),
        ]);

        $questions = (new AiService)->generateGeneralTest(
            "Module: Databases\nPrimary key is a field that uniquely identifies records. "
            .'Foreign key refers to a field that links tables. Index is a structure that improves lookup speed.',
            2,
            previousQuestions: [['question_text' => $recentQuestion['question']]],
        );

        $this->assertNotSame($recentQuestion['question'], $questions[0]['question']);
        $this->assertCount(2, $questions);
    }

    public function test_general_test_falls_back_when_ai_assigns_an_unapproved_topic(): void
    {
        $this->fakeLocalAiWithQuestions([
            $this->generalQuestion('Which concept connects related records?', 'Unapproved topic'),
        ]);

        $questions = (new AiService)->generateGeneralTest(
            "Module: Databases\nA foreign key links rows in related tables.",
            1,
        );

        $this->assertSame('Databases', $questions[0]['topic']);
    }

    public function test_general_test_falls_back_to_local_questions_when_the_ai_endpoint_fails(): void
    {
        config(['ai.api_base' => 'http://127.0.0.1:1234/v1']);
        Http::preventStrayRequests();
        Http::fake([
            $this->chatCompletionsUrl() => Http::response([], 503),
        ]);

        $questions = (new AiService)->generateGeneralTest(
            'Module: Databases. Primary key is a field that uniquely identifies records. '
            .'Foreign key refers to a field that links tables. Index is a structure that improves lookup speed.',
            2,
        );

        $this->assertCount(2, $questions);
        $this->assertContains($questions[0]['correct_answer'], $questions[0]['options']);
        Http::assertSentCount(1);
    }

    public function test_general_test_fallback_keeps_question_wording_unique_for_a_single_concept(): void
    {
        config(['ai.api_base' => 'http://127.0.0.1:1234/v1']);
        Http::preventStrayRequests();
        Http::fake([
            $this->chatCompletionsUrl() => Http::response([], 503),
        ]);

        $questions = (new AiService)->generateGeneralTest(
            "Module: Biology\nPhotosynthesis is the process by which plants convert sunlight into chemical energy.",
            30,
        );
        $normalizedQuestions = array_map(
            static fn (array $question): string => preg_replace(
                '/[^\pL\pN]+/u',
                '',
                mb_strtolower($question['question']),
            ) ?? '',
            $questions,
        );

        $this->assertCount(30, $questions);
        $this->assertCount(30, array_unique($normalizedQuestions));
    }

    public function test_generates_fallback_questions_from_short_reviewer_text(): void
    {
        $this->fakeLocalAiWithStems(30, 'Rephrased fallback question.');

        $questions = (new AiService)->generateSubtopicQuiz(
            'Networking',
            'TCP establishes reliable connections between network hosts and manages ordered delivery of data packets.',
            1,
        );

        $this->assertNotEmpty($questions);
        $this->assertCount(4, $questions[0]['options']);
        $this->assertContains($questions[0]['correct_answer'], $questions[0]['options']);
        $this->assertSame('Rephrased fallback question.', $questions[0]['question']);
    }

    public function test_keeps_local_question_stems_when_local_ai_returns_invalid_json(): void
    {
        $this->fakeLocalAiContent('This is not valid JSON.');

        $questions = (new AiService)->generateSubtopicQuiz(
            'Databases',
            'Primary key is a field that uniquely identifies records. Foreign key refers to a field that links tables. '
            .'Index is a structure that improves lookup speed. Normalization means organizing data to reduce redundancy.',
            1,
        );

        $this->assertStringContainsString('Primary key', $questions[0]['explanation']);
        $this->assertNotSame('Rephrased question stem.', $questions[0]['question']);
        $this->assertContains($questions[0]['correct_answer'], $questions[0]['options']);
    }

    public function test_keeps_local_question_stems_when_local_ai_returns_an_error(): void
    {
        config(['ai.api_base' => 'http://127.0.0.1:1234/v1']);
        Http::preventStrayRequests();
        Http::fake([
            $this->chatCompletionsUrl() => Http::response([], 503),
        ]);

        $questions = (new AiService)->generateSubtopicQuiz(
            'Databases',
            'Primary key is a field that uniquely identifies records. Foreign key refers to a field that links tables. '
            .'Index is a structure that improves lookup speed. Normalization means organizing data to reduce redundancy.',
            1,
        );

        $this->assertStringContainsString('Primary key', $questions[0]['explanation']);
        $this->assertCount(4, $questions[0]['options']);
        $this->assertContains($questions[0]['correct_answer'], $questions[0]['options']);
    }

    public function test_empty_reviewer_content_has_a_clear_error(): void
    {
        $this->expectExceptionMessage('The reviewer has no usable sentences');
        (new AiService)->generateSubtopicQuiz('Empty', '   ', 2);
    }

    public function test_study_advice_is_generated_locally(): void
    {
        Http::preventStrayRequests();

        $advice = (new AiService)->generateStudyAdvice([
            ['topic' => 'Databases', 'is_correct' => false, 'question_text' => 'What is a primary key?'],
        ]);

        $this->assertStringContainsString('Databases', $advice);
        Http::assertNothingSent();
    }

    public function test_explanation_is_generated_by_the_local_ai_endpoint(): void
    {
        $this->fakeLocalAiContent('A primary key is the unique value used to identify one row in a table.');

        $explanation = (new AiService)->generateExplanation('Primary key uniquely identifies each row.');

        $this->assertSame('A primary key is the unique value used to identify one row in a table.', $explanation);
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === $this->chatCompletionsUrl()
            && $request['max_tokens'] === 250);
    }

    public function test_interprets_supported_achievement_rules_locally(): void
    {
        $interpretation = (new AiService)->interpretAchievementRequirements('Complete 3 lessons and earn at least 100 points.');

        $this->assertGreaterThanOrEqual(0.95, $interpretation['confidence']);
        $this->assertContains(['type' => 'lesson_completion', 'required' => 3], $interpretation['conditions']);
        $this->assertContains(['type' => 'total_points', 'minimum' => 100], $interpretation['conditions']);
    }

    private function fakeLocalAiWithStems(int $count, string $stem): void
    {
        $this->fakeLocalAiContent(json_encode([
            'stems' => array_fill(0, $count, $stem),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function fakeLocalAiWithQuestions(array $questions): void
    {
        $this->fakeLocalAiContent(json_encode([
            'questions' => $questions,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{question: string, options: array<int, string>, correct_answer: string, explanation: string, topic: string, style?: string}
     */
    private function generalQuestion(string $question, string $topic, string $style = 'concept'): array
    {
        return [
            'question' => $question,
            'options' => ['Foreign key', 'Primary key', 'Index', 'Normalization'],
            'correct_answer' => 'Foreign key',
            'explanation' => 'A foreign key links rows in related tables.',
            'topic' => $topic,
            'style' => $style,
        ];
    }

    private function fakeLocalAiContent(string $content): void
    {
        config([
            'ai.api_base' => 'http://127.0.0.1:1234/v1',
            'ai.temperature' => 0.7,
        ]);
        Http::preventStrayRequests();
        Http::fake([
            $this->chatCompletionsUrl() => Http::response([
                'choices' => [
                    ['message' => ['content' => $content]],
                ],
            ]),
        ]);
    }

    private function chatCompletionsUrl(): string
    {
        return 'http://127.0.0.1:1234/v1/chat/completions';
    }
}
