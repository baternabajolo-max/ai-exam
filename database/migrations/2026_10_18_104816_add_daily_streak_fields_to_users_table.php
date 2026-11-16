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
        Schema::table('users', function (Blueprint $table) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedSmallInteger('daily_streak')->default(0)->after('total_points');
                $table->date('daily_streak_last_completed_at')->nullable()->after('daily_streak');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn(['daily_streak', 'daily_streak_last_completed_at']);
            });
        });
    }
};
