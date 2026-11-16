<?php

namespace App\Models;

use Database\Factories\QuestionBankItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionBankItem extends Model
{
    /** @use HasFactory<QuestionBankItemFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'author_id',
        'subtopic_id',
        'question',
        'options',
        'correct_answer',
        'explanation',
        'topic',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function subtopic(): BelongsTo
    {
        return $this->belongsTo(Subtopic::class);
    }

    public function testQuestions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
