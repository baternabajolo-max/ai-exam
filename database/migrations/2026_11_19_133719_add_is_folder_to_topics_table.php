<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
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
        Schema::table('topics', function (Blueprint $table): void {
            $table->boolean('is_folder')->default(false);
            $table->index(['course_id', 'is_folder']);
            $table->dropUnique(['course_id', 'name']);
            $table->dropUnique(['course_id', 'name_key']);
            $table->unique(['course_id', 'name', 'is_folder']);
            $table->unique(['course_id', 'name_key', 'is_folder']);
        });

        DB::table('topics')
            ->whereExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('question_bank_items')
                    ->whereColumn('question_bank_items.topic_id', 'topics.id');
            })
            ->update(['is_folder' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table): void {
            $table->dropUnique(['course_id', 'name', 'is_folder']);
            $table->dropUnique(['course_id', 'name_key', 'is_folder']);
            $table->dropIndex(['course_id', 'is_folder']);
            $table->unique(['course_id', 'name']);
            $table->unique(['course_id', 'name_key']);
            $table->dropColumn('is_folder');
        });
    }
};
