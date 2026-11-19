<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $topicsByProgramAndName = [];
        DB::table('topics')
            ->get(['id', 'course_id', 'name_key'])
            ->each(function (object $topic) use (&$topicsByProgramAndName): void {
                $topicsByProgramAndName[$topic->course_id.':'.$topic->name_key] = $topic->id;
            });

        DB::table('questions as exam_questions')
            ->join('tests as exams', 'exams.id', '=', 'exam_questions.test_id')
            ->leftJoin('question_bank_items as bank_items', 'bank_items.id', '=', 'exam_questions.question_bank_item_id')
            ->whereNotNull('exams.course_id')
            ->whereNotNull('exam_questions.topic')
            ->select([
                'exam_questions.id',
                'exams.course_id',
                'exam_questions.topic',
                'bank_items.author_id',
            ])
            ->orderBy('exam_questions.id')
            ->chunkById(500, function ($questions) use (&$topicsByProgramAndName): void {
                foreach ($questions as $question) {
                    $name = trim((string) $question->topic);
                    if ($name === '') {
                        continue;
                    }

                    $nameKey = mb_strtolower($name, 'UTF-8');
                    $lookupKey = $question->course_id.':'.$nameKey;
                    $topicId = $topicsByProgramAndName[$lookupKey] ?? null;

                    if ($topicId === null) {
                        $topicId = DB::table('topics')->insertGetId([
                            'course_id' => $question->course_id,
                            'created_by' => $question->author_id,
                            'name' => $name,
                            'name_key' => $nameKey,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $topicsByProgramAndName[$lookupKey] = $topicId;
                    }

                    DB::table('questions')
                        ->where('id', $question->id)
                        ->update(['topic_id' => $topicId]);
                }
            }, 'exam_questions.id', 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('questions as exam_questions')
            ->leftJoin('question_bank_items as bank_items', 'bank_items.id', '=', 'exam_questions.question_bank_item_id')
            ->select([
                'exam_questions.id',
                'bank_items.topic_id as source_topic_id',
            ])
            ->orderBy('exam_questions.id')
            ->chunkById(500, function ($questions): void {
                foreach ($questions as $question) {
                    DB::table('questions')
                        ->where('id', $question->id)
                        ->update(['topic_id' => $question->source_topic_id]);
                }
            }, 'exam_questions.id', 'id');
    }
};
