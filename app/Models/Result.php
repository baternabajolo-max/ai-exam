<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'test_id',
        'score',
        'total_questions',
        'passed',
        'answers_summary', // The AI can keep using this one
        'answers',         // We will use this one for the review!
        'started_at',
        'time_taken_seconds',
        'submission_type',
    ];

    protected $casts = [
        'answers_summary' => 'array',
        'answers' => 'array', // Automatically handles JSON
        'passed' => 'boolean',
        'started_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
