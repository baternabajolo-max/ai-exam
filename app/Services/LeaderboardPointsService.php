<?php

namespace App\Services;

use App\Models\Subtopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeaderboardPointsService
{
    public function recalculateFor(User $student): void
    {
        $subtopics = Subtopic::query()
            ->whereHas('module', fn (Builder $query) => $query->forStudent($student))
            ->with('module')
            ->get();
        $totalPoints = 0;

        foreach ($subtopics as $subtopic) {
            $totalPoints += $subtopic->earnedPointsForUser((int) $student->getKey());
        }

        if ((int) $student->total_points !== $totalPoints) {
            $student->update(['total_points' => $totalPoints]);
        }
    }
}
