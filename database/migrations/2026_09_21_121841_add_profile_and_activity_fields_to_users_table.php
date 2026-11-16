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
            $table->string('badge_icon')->nullable()->after('total_points');
            $table->timestamp('last_active_at')->nullable()->after('badge_icon');
            $table->unsignedSmallInteger('ai_generations_today')->default(0)->after('last_active_at');
            $table->date('ai_generation_date')->nullable()->after('ai_generations_today');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['badge_icon', 'last_active_at', 'ai_generations_today', 'ai_generation_date']);
        });
    }
};
