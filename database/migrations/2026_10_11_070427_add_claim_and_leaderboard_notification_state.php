<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_notifications', function (Blueprint $table): void {
            $table->foreignId('admin_id')->nullable()->change();
        });

        Schema::table('user_achievements', function (Blueprint $table): void {
            $table->timestamp('claimed_at')->nullable()->after('achievement_id');
        });

        Schema::create('department_leaderboard_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rank');
            $table->unsignedInteger('points');
            $table->timestamps();

            $table->unique(['department_id', 'user_id']);
            $table->index(['department_id', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_leaderboard_positions');

        Schema::table('user_achievements', function (Blueprint $table): void {
            $table->dropColumn('claimed_at');
        });

        Schema::table('student_notifications', function (Blueprint $table): void {
            $table->foreignId('admin_id')->nullable(false)->change();
        });
    }
};
