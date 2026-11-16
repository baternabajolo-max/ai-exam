<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FlashcardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_can_create_a_personal_flashcard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post(route('student.flashcards.store'), ['front' => 'SQL Injection', 'back' => 'Unsafe query input', 'color' => 'blue'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('flashcards', ['user_id' => $student->id, 'front' => 'SQL Injection']);
    }
}
