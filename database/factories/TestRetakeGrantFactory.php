<?php

namespace Database\Factories;

use App\Models\Test;
use App\Models\TestRetakeGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends Factory<TestRetakeGrant>
 */
class TestRetakeGrantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'test_id' => fn (): int => Test::query()
                ->where('is_scheduled_exam', true)
                ->value('id')
                ?? throw new RuntimeException('Create a scheduled exam before a retake grant.'),
            'student_id' => fn (): int => User::query()
                ->where('role', 'student')
                ->value('id')
                ?? throw new RuntimeException('Create a student before a retake grant.'),
            'granted_by' => fn (): int => User::query()
                ->whereIn('role', ['admin', 'super-admin'])
                ->value('id')
                ?? throw new RuntimeException('Create an Admin or Faculty account before a retake grant.'),
            'granted_at' => now(),
            'started_at' => null,
            'consumed_at' => null,
        ];
    }
}
