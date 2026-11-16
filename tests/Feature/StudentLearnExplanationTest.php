<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentLearnExplanationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_student_receives_an_explanation_for_a_selected_word_and_its_context(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'approved',
        ]);
        config([
            'ai.api_base' => 'http://127.0.0.1:1234/v1',
            'ai.temperature' => 0.7,
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:1234/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Mathematics is the study of numbers, patterns, shapes, and logical relationships.']],
                ],
            ]),
        ]);

        $response = $this->actingAs($student)->postJson(route('student.learn.explain'), [
            'text' => 'MATHEMATICS',
            'context' => 'Mathematics helps describe patterns and solve problems.',
        ]);

        $response->assertOk()->assertExactJson([
            'success' => true,
            'explanation' => 'Mathematics is the study of numbers, patterns, shapes, and logical relationships.',
        ]);

        Http::assertSent(fn (ClientRequest $request): bool => $request['messages'][1]['content'] === json_encode([
            'selected_text' => 'MATHEMATICS',
            'reviewer_context' => 'Mathematics helps describe patterns and solve problems.',
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
            && str_contains($request['messages'][0]['content'], 'Never say that no text was provided'));
    }

    public function test_guest_cannot_request_a_reviewer_text_explanation(): void
    {
        $response = $this->post(route('student.learn.explain'), [
            'text' => 'A selected reviewer sentence.',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_student_gets_a_clear_error_when_local_ai_is_unavailable(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'approved',
        ]);
        config(['ai.api_base' => 'http://127.0.0.1:1234/v1']);
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:1234/v1/chat/completions' => Http::response([], 503),
        ]);

        $response = $this->actingAs($student)->postJson(route('student.learn.explain'), [
            'text' => 'A primary key uniquely identifies each row.',
        ]);

        $response->assertInternalServerError()->assertExactJson([
            'success' => false,
            'message' => 'The AI explanation service is unavailable. Please try again shortly.',
        ]);
    }
}
