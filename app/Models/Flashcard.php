<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flashcard extends Model
{
    protected $fillable = ['user_id', 'subtopic_id', 'front', 'back', 'description', 'color'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subtopic()
    {
        return $this->belongsTo(Subtopic::class);
    }
}
