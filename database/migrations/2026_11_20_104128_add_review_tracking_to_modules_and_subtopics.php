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
        Schema::table('modules', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('course_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('subtopics', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('module_id')->constrained('users')->nullOnDelete();
            $table->boolean('needs_revision')->default(false)->after('text_extraction_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subtopics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('needs_revision');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
