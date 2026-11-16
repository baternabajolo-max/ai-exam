<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'department_id'];

    protected static function booted()
    {
        static::creating(function ($course) {
            if (empty($course->code)) {
                $course->code = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $course->name), 0, 4)).rand(10, 99);
            }
        });
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }
}
