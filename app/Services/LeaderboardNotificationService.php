<?php

namespace App\Services;

use App\Models\DepartmentLeaderboardPosition;
use App\Models\StudentNotification;
use App\Models\User;

class LeaderboardNotificationService
{
    public function syncDepartment(int $departmentId): void
    {
        $students = User::query()
            ->where('role', 'student')
            ->where('department_id', $departmentId)
            ->orderByDesc('total_points')
            ->orderBy('name')
            ->get(['id', 'name', 'total_points']);
        $previousPositions = DepartmentLeaderboardPosition::query()
            ->where('department_id', $departmentId)
            ->with('user:id,name')
            ->get()
            ->keyBy('user_id');
        $currentPositions = $students->values()->mapWithKeys(
            fn (User $student, int $index): array => [$student->id => [
                'rank' => $index + 1,
                'student' => $student,
            ]],
        );

        foreach ($currentPositions as $studentId => $current) {
            $previous = $previousPositions->get($studentId);
            if ($previous === null && $previousPositions->isNotEmpty() && $current['rank'] <= 3) {
                StudentNotification::create([
                    'student_id' => $studentId,
                    'admin_id' => null,
                    'subject' => 'You reached the leaderboard top 3',
                    'message' => 'Congratulations! You are now ranked #'.$current['rank'].' in your department.',
                ]);

                continue;
            }

            if ($previous !== null && $previous->rank <= 3 && $current['rank'] > $previous->rank) {
                StudentNotification::create([
                    'student_id' => $studentId,
                    'admin_id' => null,
                    'subject' => 'Your leaderboard position changed',
                    'message' => 'Another student passed you. You moved from rank #'.$previous->rank.' to #'.$current['rank'].' in your department.',
                ]);
            }
        }

        foreach ($students as $index => $student) {
            DepartmentLeaderboardPosition::updateOrCreate(
                ['department_id' => $departmentId, 'user_id' => $student->id],
                ['rank' => $index + 1, 'points' => max(0, (int) $student->total_points)],
            );
        }

        DepartmentLeaderboardPosition::query()
            ->where('department_id', $departmentId)
            ->whereNotIn('user_id', $students->pluck('id'))
            ->delete();
    }
}
