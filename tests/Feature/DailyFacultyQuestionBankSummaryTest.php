<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\QuestionBankItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DailyFacultyQuestionBankSummaryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_daily_command_notifies_each_admin_with_previous_manila_day_faculty_counts_once(): void
    {
        $this->travelTo('2026-03-20 04:00:00');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $facultyA = User::factory()->create(['name' => 'Faculty A', 'role' => 'admin', 'department_id' => $department->id]);
        $facultyB = User::factory()->create(['name' => 'Faculty B', 'role' => 'admin', 'department_id' => $department->id]);
        $admin = User::factory()->create(['name' => 'Admin One', 'role' => 'super-admin']);
        $secondAdmin = User::factory()->create(['name' => 'Admin Two', 'role' => 'super-admin']);

        $this->createQuestion($course, $facultyA, 'At report start', '2026-03-18 16:00:00');
        $this->createQuestion($course, $facultyA, 'Before report end', '2026-03-19 15:59:59');
        $this->createQuestion($course, $facultyA, 'At report end', '2026-03-19 16:00:00');
        $this->createQuestion($course, $facultyB, 'Before report start', '2026-03-18 15:59:59');

        $this->artisan('reports:faculty-question-bank-daily-summary')->assertExitCode(0);

        $notification = $admin->notifications()->firstOrFail();
        $this->assertSame('2026-03-19', $notification->data['report_date']);
        $this->assertSame(2, $notification->data['total_questions_added']);
        $this->assertSame([
            ['faculty_name' => 'Faculty A', 'questions_added' => 2],
            ['faculty_name' => 'Faculty B', 'questions_added' => 0],
        ], $notification->data['faculty_counts']);
        $this->assertSame(1, $secondAdmin->notifications()->count());

        $this->artisan('reports:faculty-question-bank-daily-summary')->assertExitCode(0);
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $secondAdmin->notifications()->count());

        $this->actingAs($admin)
            ->get(route('super-admin.dashboard'))
            ->assertSee('Daily Question Bank Summary')
            ->assertSee('2 question(s) added')
            ->assertSee('Faculty A: 2')
            ->assertSee('admin-notification-trigger')
            ->assertSee(route('super-admin.notifications.read', $notification->id), false)
            ->assertSee('window.location.assign(trigger.href)', false);

        $this->actingAs($secondAdmin)
            ->post(route('super-admin.notifications.read', $notification->id))
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('super-admin.notifications.read', $notification->id))
            ->assertRedirect();
        $this->assertNotNull($admin->notifications()->whereKey($notification->id)->firstOrFail()->read_at);
    }

    private function createQuestion(Course $course, User $faculty, string $text, string $createdAt): void
    {
        $question = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $faculty->id,
            'question' => $text,
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);
        $question->forceFill([
            'created_at' => CarbonImmutable::parse($createdAt, 'UTC'),
            'updated_at' => CarbonImmutable::parse($createdAt, 'UTC'),
        ])->saveQuietly();
    }
}
