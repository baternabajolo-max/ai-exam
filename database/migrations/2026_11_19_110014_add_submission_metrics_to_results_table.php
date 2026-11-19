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
        Schema::table('results', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('passed');
            $table->unsignedInteger('time_taken_seconds')->nullable()->after('started_at');
            $table->string('submission_type')->default('manual')->after('time_taken_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'time_taken_seconds', 'submission_type']);
        });
    }
};
