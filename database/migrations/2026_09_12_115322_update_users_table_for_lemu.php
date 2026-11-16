<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('email');
            $table->string('status')->default('pending')->after('role');

            $table->unsignedBigInteger('department_id')->nullable()->after('status');
            $table->unsignedBigInteger('course_id')->nullable()->after('department_id');

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['course_id']);
            $table->dropColumn(['role', 'status', 'department_id', 'course_id']);
        });
    }
};
