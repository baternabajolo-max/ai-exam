<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = ['title', 'description', 'icon', 'required_points', 'requirements_text', 'requirements', 'badge_image', 'is_active'];

    protected function casts(): array
    {
        return ['requirements' => 'array', 'is_active' => 'boolean'];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->using(UserAchievement::class)
            ->withPivot('claimed_at')
            ->withTimestamps();
    }
}
