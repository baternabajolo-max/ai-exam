<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasFactory, MustVerifyEmailTrait, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'status',
        'department_id',
        'course_id',
        'password',
        'student_id_number',
        'total_points',
        'daily_streak',
        'daily_streak_last_completed_at',
        'badge_icon',
        'profile_picture_path',
        'background_picture_path',
        'last_active_at',
        'ai_generations_today',
        'ai_generation_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_required' => 'boolean',
            'birthdate' => 'date',
            'last_active_at' => 'datetime',
            'ai_generation_date' => 'date',
            'daily_streak_last_completed_at' => 'date',
        ];
    }

    public function getDailyStreakStateAttribute(): string
    {
        $lastCompletedAt = $this->daily_streak_last_completed_at?->toDateString();

        if ($lastCompletedAt === null) {
            return 'inactive';
        }

        if (in_array($lastCompletedAt, [today()->toDateString(), today()->subDay()->toDateString()], true)) {
            return 'active';
        }

        return $lastCompletedAt === today()->subDays(2)->toDateString() ? 'frozen' : 'inactive';
    }

    public function getVisibleDailyStreakAttribute(): int
    {
        return $this->daily_streak_state === 'inactive' ? 0 : (int) $this->daily_streak;
    }

    public function getFirstNameAttribute(): string
    {
        $nameParts = preg_split('/\s+/u', trim($this->name), 2);

        return $nameParts[0] ?? '';
    }

    public function getLastNameAttribute(): string
    {
        $nameParts = preg_split('/\s+/u', trim($this->name), 2);

        return $nameParts[1] ?? '';
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin' => 'Faculty',
            'super-admin' => 'Admin',
            'student' => 'Student',
            default => ucfirst(str_replace('-', ' ', (string) $this->role)),
        };
    }

    public function recordQuizCompletionStreak(): void
    {
        DB::transaction(function (): void {
            $student = static::query()
                ->lockForUpdate()
                ->findOrFail($this->getKey());

            $today = today()->toDateString();
            $lastCompletedAt = $student->daily_streak_last_completed_at?->toDateString();

            if ($lastCompletedAt === $today) {
                return;
            }

            if ($lastCompletedAt === today()->subDay()->toDateString()) {
                $student->daily_streak++;
            } elseif ($lastCompletedAt !== today()->subDays(2)->toDateString()) {
                $student->daily_streak = 1;
            }

            $student->daily_streak_last_completed_at = $today;
            $student->save();
        });

        $this->refresh();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeInFacultyCollege(Builder $query, User $faculty): void
    {
        $query->where('department_id', $faculty->department_id);
    }

    public function questionBankItems(): HasMany
    {
        return $this->hasMany(QuestionBankItem::class, 'author_id');
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->using(UserAchievement::class)
            ->withPivot('claimed_at')
            ->withTimestamps();
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function receivedNotifications()
    {
        return $this->hasMany(StudentNotification::class, 'student_id');
    }

    public function sentNotifications()
    {
        return $this->hasMany(StudentNotification::class, 'admin_id');
    }

    public function flashcards()
    {
        return $this->hasMany(Flashcard::class);
    }
}
