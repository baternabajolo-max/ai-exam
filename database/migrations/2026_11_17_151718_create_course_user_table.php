<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'course_id']);
            $table->index(['course_id', 'user_id']);
        });

        DB::table('users')
            ->where('role', 'admin')
            ->whereNotNull('course_id')
            ->orderBy('id')
            ->get(['id', 'course_id'])
            ->each(fn (object $faculty) => DB::table('course_user')->insert([
                'user_id' => $faculty->id,
                'course_id' => $faculty->course_id,
            ]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_user');
    }
};
