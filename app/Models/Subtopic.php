<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subtopic extends Model
{
    public const TEXT_EXTRACTION_NOT_REQUIRED = 'not_required';

    public const TEXT_EXTRACTION_PENDING = 'pending';

    public const TEXT_EXTRACTION_PROCESSING = 'processing';

    public const TEXT_EXTRACTION_COMPLETED = 'completed';

    public const TEXT_EXTRACTION_FAILED = 'failed';

    protected $fillable = [
        'module_id',
        'title',
        'description',
        'file_path',
        'extracted_text',
        'text_extraction_status',
        'needs_revision',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'needs_revision' => 'boolean',
        ];
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate the max points earned based on a 30-point scale.
     */
    public function getEarnedPointsAttribute()
    {
        $userId = auth()->id();

        return $userId ? $this->earnedPointsForUser((int) $userId) : 0;
    }

    public function earnedPointsForUser(int $userId): int
    {
        if (! $userId) {
            return 0;
        }

        // 1. Find ALL test IDs where the title or topic contains the subtopic name
        // (This allows "ggs - Review Quiz" to perfectly match "ggs")
        $departmentId = $this->module?->department_id;
        $courseId = $this->module?->course_id;
        $testIds = Test::when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->when($courseId !== null, fn ($query) => $query->where('course_id', $courseId))
            ->where(function ($query) {
                $query->where('title', 'LIKE', '%'.$this->title.'%')
                    ->orWhere('topic', 'LIKE', '%'.$this->title.'%');
            })
            ->pluck('id');

        if ($testIds->isEmpty()) {
            return 0; // No tests generated for this subtopic yet
        }

        // 2. Find the student's most recent result across these matching tests.
        $bestResult = Result::where('user_id', $userId)
            ->whereIn('test_id', $testIds)
            ->latest('created_at')
            ->latest('id')
            ->first();

        if (! $bestResult) {
            return 0; // The test exists, but the user hasn't submitted it yet
        }

        // 3. Count total questions directly from the saved snapshot to guarantee 100% accuracy
        $snapshot = $bestResult->answers;
        $questionsData = is_string($snapshot) ? json_decode($snapshot, true) : json_decode(json_encode($snapshot), true);
        $totalQuestions = is_array($questionsData) ? count($questionsData) : 0;

        // Emergency fallback just in case the snapshot is empty
        if ($totalQuestions === 0) {
            $totalQuestions = Question::where('test_id', $bestResult->test_id)->count();
        }

        if ($totalQuestions === 0) {
            return 0; // Prevent divide-by-zero errors
        }

        // 4. Calculate points (e.g., 8 / 10 = 0.80 * 30 = 24)
        $percentage = $bestResult->score / $totalQuestions;

        return round($percentage * 30);
    }
}
