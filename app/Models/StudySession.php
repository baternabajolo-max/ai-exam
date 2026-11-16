<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'subtopic_id',
        'activity_date',
        'active_seconds',
        'started_at',
        'last_heartbeat_at',
        'ended_at',
        'pause_reason',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'active_seconds' => 'integer',
            'started_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function subtopic(): BelongsTo
    {
        return $this->belongsTo(Subtopic::class);
    }
}
