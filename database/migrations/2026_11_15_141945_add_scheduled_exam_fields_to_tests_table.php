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
        Schema::table('tests', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('course_id')->constrained('users')->nullOnDelete();
            $table->boolean('is_scheduled_exam')->default(false)->after('is_general');
            $table->boolean('is_published')->default(false)->after('is_scheduled_exam');
            $table->timestamp('available_from')->nullable()->after('is_published');
            $table->timestamp('available_until')->nullable()->after('available_from');

            $table->index(['course_id', 'is_scheduled_exam', 'is_published', 'available_from'], 'tests_course_schedule_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropIndex('tests_course_schedule_index');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('course_id');
            $table->dropColumn(['is_scheduled_exam', 'is_published', 'available_from', 'available_until']);
        });
    }
};
