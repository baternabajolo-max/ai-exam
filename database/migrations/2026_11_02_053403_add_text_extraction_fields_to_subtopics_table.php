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
        Schema::table('subtopics', function (Blueprint $table) {
            $table->longText('extracted_text')->nullable();
            $table->string('text_extraction_status')->default('not_required');
        });

        DB::table('subtopics')
            ->whereNotNull('file_path')
            ->where('file_path', 'like', '%.pdf')
            ->update(['text_extraction_status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subtopics', function (Blueprint $table) {
            $table->dropColumn(['extracted_text', 'text_extraction_status']);
        });
    }
};
