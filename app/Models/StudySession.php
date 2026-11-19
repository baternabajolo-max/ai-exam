<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    public const TOPIC_STUDY_CAP_SECONDS = 1800;

    public const MIN_HEARTBEAT_SECONDS = 25;

    public const MAX_HEARTBEAT_SECONDS = 30;

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

    public function scopeVerifiedByQuiz(Builder $query): Builder
    {
        return $query->whereExists(function ($attemptQuery): void {
            $attemptQuery->selectRaw('1')
                ->from('results')
                ->join('tests', 'tests.id', '=', 'results.test_id')
                ->whereColumn('results.user_id', 'study_sessions.user_id')
                ->whereColumn('tests.subtopic_id', 'study_sessions.subtopic_id')
                ->where('tests.is_general', false);
        });
    }
}
