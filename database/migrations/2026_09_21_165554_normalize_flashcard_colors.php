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
        DB::table('flashcards')->whereIn('color', ['purple', 'orange', 'gray'])->update(['color' => 'blue']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
