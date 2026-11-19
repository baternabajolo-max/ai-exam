<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Test extends Model
{
    use HasFactory;

    public const PASSING_SCORE_PERCENTAGE = 85;

    public const APPROVAL_STATUS_PENDING = 'pending';

    public const APPROVAL_STATUS_APPROVED = 'approved';

    public const APPROVAL_STATUS_REJECTED = 'rejected';

    public const QUESTION_TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const QUESTION_TYPE_IDENTIFICATION = 'identification';

    public const QUESTION_TYPE_MIXED = 'mixed';

    protected $attributes = [
        'approval_status' => self::APPROVAL_STATUS_APPROVED,
    ];

    protected $fillable = [
        'title',
        'description',
        'topic',
        'department_id',
        'course_id',
        'created_by',
        'is_general',
        'is_scheduled_exam',
        'is_published',
        'approval_status',
        'rejection_comment',
        'reviewed_by',
        'reviewed_at',
        'available_from',
        'available_until',
        'time_limit',
        'duration_minutes',
        'subtopic_id',
        'question_type',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function retakeGrants(): HasMany
    {
        return $this->hasMany(TestRetakeGrant::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subtopic(): BelongsTo
    {
        return $this->belongsTo(Subtopic::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'is_general' => 'boolean',
            'is_scheduled_exam' => 'boolean',
            'is_published' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function scheduleStatus(?Carbon $now = null): string
    {
        $now ??= now();

        if ($this->approval_status === 'pending') {
            return 'Pending approval';
        }

        if ($this->approval_status === 'rejected') {
            return 'Rejected';
        }

        if (! $this->is_published) {
            return 'Draft';
        }

        if ($this->available_until?->isBefore($now)) {
            return 'Closed';
        }

        if ($this->available_from?->isAfter($now)) {
            return 'Scheduled';
        }

        return 'Active';
    }

    public function isAvailableAt(?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->is_scheduled_exam
            && $this->is_published
            && $this->approval_status === self::APPROVAL_STATUS_APPROVED
            && ($this->available_from === null || $this->available_from->lte($now))
            && ($this->available_until === null || $this->available_until->gte($now));
    }

    public function isEditableBy(User $user, ?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->is_scheduled_exam
            && (int) $this->created_by === (int) $user->getKey()
            && $this->available_from?->isAfter($now) === true;
    }
}
