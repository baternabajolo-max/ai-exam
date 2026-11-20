<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    // MUST include 'logo_path' here or the image won't save
    protected $fillable = [
        'title',
        'description',
        'logo_path',
        'department_id',
        'course_id',
        'created_by',
    ];

    public function scopeForStudent(Builder $query, User $student): void
    {
        $query->where('department_id', $student->department_id);

        $courseIds = Course::query()
            ->where('department_id', $student->department_id)
            ->limit(2)
            ->pluck('id');

        if ($courseIds->count() >= 2) {
            if ($student->course_id === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('course_id', $student->course_id);

            return;
        }

        $query->where(function (Builder $courseQuery) use ($student): void {
            $courseQuery->whereNull('course_id');

            if ($student->course_id !== null) {
                $courseQuery->orWhere('course_id', $student->course_id);
            }
        });
    }

    public function scopeForFaculty(Builder $query, User $faculty): void
    {
        $query->where('department_id', $faculty->department_id);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function subtopics(): HasMany
    {
        return $this->hasMany(Subtopic::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
