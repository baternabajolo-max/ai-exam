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
        Schema::table('question_bank_items', function (Blueprint $table): void {
            $table->foreignId('topic_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->foreignId('topic_id')->nullable()->after('question_bank_item_id')->constrained()->nullOnDelete();
        });

        $topicsByProgramAndName = [];
        DB::table('question_bank_items')
            ->whereNotNull('topic')
            ->orderBy('id')
            ->chunkById(500, function ($items) use (&$topicsByProgramAndName): void {
                foreach ($items as $item) {
                    $name = trim((string) $item->topic);
                    if ($name === '') {
                        continue;
                    }

                    $nameKey = mb_strtolower($name, 'UTF-8');
                    $lookupKey = $item->course_id.':'.$nameKey;
                    $topicId = $topicsByProgramAndName[$lookupKey] ?? null;

                    if ($topicId === null) {
                        $topicId = DB::table('topics')->insertGetId([
                            'course_id' => $item->course_id,
                            'created_by' => $item->author_id,
                            'name' => $name,
                            'name_key' => $nameKey,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $topicsByProgramAndName[$lookupKey] = $topicId;
                    }

                    DB::table('question_bank_items')
                        ->where('id', $item->id)
                        ->update(['topic_id' => $topicId]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('topic_id');
        });

        Schema::table('question_bank_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('topic_id');
        });
    }
};
