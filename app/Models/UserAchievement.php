<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserAchievement extends Pivot
{
    protected $table = 'user_achievements';

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
        ];
    }
}
