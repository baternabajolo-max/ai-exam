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
        Schema::create('flashcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subtopic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('front', 1000);
            $table->string('back', 1000)->nullable();
            $table->text('description')->nullable();
            $table->string('color', 20)->default('blue');
            $table->timestamps();

            $table->index(['user_id', 'color', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flashcards');
    }
};
