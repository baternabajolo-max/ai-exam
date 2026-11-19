<?php

namespace App\Models;

use Database\Factories\TestRetakeGrantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestRetakeGrant extends Model
{
    /** @use HasFactory<TestRetakeGrantFactory> */
    use HasFactory;

    protected $fillable = [
        'test_id',
        'student_id',
        'granted_by',
        'granted_at',
        'started_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'started_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
