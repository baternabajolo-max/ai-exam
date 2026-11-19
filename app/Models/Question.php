<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'question_bank_item_id',
        'topic_id',
        'question_type',
        'question',
        'options',
        'correct_answer',
        'explanation',
        'topic',
        'image_path',
        'difficulty',
    ];

    // This automatically converts the JSON string to an array for your Blade views
    protected $casts = [
        'options' => 'array',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function questionBankItem(): BelongsTo
    {
        return $this->belongsTo(QuestionBankItem::class);
    }

    public function topicRecord(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }
}
