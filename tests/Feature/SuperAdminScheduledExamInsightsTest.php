<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SuperAdminScheduledExamInsightsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_dashboard_does_not_show_manual_examination_insights(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $admin = User::factory()->create(['role' => 'super-admin']);
        $this->actingAs($admin)
            ->get(route('super-admin.dashboard', [
                'exam_course_id' => 999,
                'exam_id' => 999,
            ]))
            ->assertOk()
            ->assertDontSee('Manual examination insights')
            ->assertDontSee('dashboard-exam-course');
    }

    public function test_admin_dashboard_preserves_the_college_filter_without_exam_insight_filters(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $admin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($admin)
            ->get(route('super-admin.dashboard', ['college_id' => $department->id]))
            ->assertOk()
            ->assertSee('id="super-admin-college-filter"', false)
            ->assertSee('Computing')
            ->assertDontSee('Manual examination insights');
    }
}
