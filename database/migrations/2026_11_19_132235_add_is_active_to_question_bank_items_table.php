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
        Schema::table('question_bank_items', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true);
            $table->index(['course_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank_items', function (Blueprint $table): void {
            $table->dropIndex(['course_id', 'is_active']);
            $table->dropColumn('is_active');
        });
    }
};
