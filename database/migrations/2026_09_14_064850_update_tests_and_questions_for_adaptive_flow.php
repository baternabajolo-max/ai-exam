<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->boolean('is_general')->default(true);
            $table->string('topic')->nullable(); // Used for targeted weak-area tests
            $table->integer('time_limit')->nullable(); // In minutes (e.g., 120 for 2 hours)
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('topic')->default('General'); // Categorizes questions to track weak areas
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn(['is_general', 'topic', 'time_limit']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('topic');
        });
    }
};
