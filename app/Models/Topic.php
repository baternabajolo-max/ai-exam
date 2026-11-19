<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    use HasFactory;

    protected $attributes = [
        'is_folder' => true,
    ];

    protected $fillable = [
        'course_id',
        'created_by',
        'is_folder',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'is_folder' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Topic $topic): void {
            $topic->name = trim($topic->name);
            $topic->name_key = mb_strtolower($topic->name, 'UTF-8');
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function bankItems(): HasMany
    {
        return $this->hasMany(QuestionBankItem::class);
    }
}
