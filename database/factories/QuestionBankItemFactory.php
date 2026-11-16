<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\QuestionBankItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends Factory<QuestionBankItem>
 */
class QuestionBankItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => fn (): int => Course::query()->value('id')
                ?? throw new RuntimeException('Create a course before a Question Bank item.'),
            'author_id' => fn (): int => User::query()
                ->where('role', 'admin')
                ->value('id')
                ?? throw new RuntimeException('Create a Faculty account before a Question Bank item.'),
            'question' => fake()->sentence().'?',
            'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
            'correct_answer' => 'Option A',
            'topic' => fake()->word(),
            'explanation' => fake()->sentence(),
        ];
    }
}
