<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'color_theme'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function tests()
    {
        return $this->hasMany(Test::class);
    }

    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
