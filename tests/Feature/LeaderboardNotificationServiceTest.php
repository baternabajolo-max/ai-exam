<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentLeaderboardPosition;
use App\Models\StudentNotification;
use App\Models\User;
use App\Services\LeaderboardNotificationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaderboardNotificationServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_is_notified_once_when_another_student_passes_them_out_of_the_top_three(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $previousFirst = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'name' => 'Previous First',
            'total_points' => 90,
        ]);
        $previousSecond = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'name' => 'Previous Second',
            'total_points' => 80,
        ]);
        $displacedStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'name' => 'Displaced Student',
            'total_points' => 70,
        ]);
        $passingStudent = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'name' => 'Passing Student',
            'total_points' => 100,
        ]);

        foreach ([
            [$previousFirst, 1, 90],
            [$previousSecond, 2, 80],
            [$displacedStudent, 3, 70],
            [$passingStudent, 4, 60],
        ] as [$student, $rank, $points]) {
            DepartmentLeaderboardPosition::create([
                'department_id' => $department->id,
                'user_id' => $student->id,
                'rank' => $rank,
                'points' => $points,
            ]);
        }

        $service = app(LeaderboardNotificationService::class);
        $service->syncDepartment($department->id);
        $service->syncDepartment($department->id);

        $this->assertDatabaseHas('student_notifications', [
            'student_id' => $displacedStudent->id,
            'subject' => 'Your leaderboard position changed',
        ]);
        $this->assertSame(
            1,
            StudentNotification::where('student_id', $displacedStudent->id)
                ->where('subject', 'Your leaderboard position changed')
                ->count(),
        );
        $this->assertDatabaseHas('department_leaderboard_positions', [
            'department_id' => $department->id,
            'user_id' => $displacedStudent->id,
            'rank' => 4,
        ]);
    }
}
