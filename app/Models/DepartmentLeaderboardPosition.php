<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentLeaderboardPosition extends Model
{
    protected $fillable = ['department_id', 'user_id', 'rank', 'points'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
