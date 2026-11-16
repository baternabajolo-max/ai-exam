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
        Schema::table('achievements', function (Blueprint $table) {
            $table->text('requirements_text')->nullable()->after('description');
            $table->json('requirements')->nullable()->after('requirements_text');
            $table->string('badge_image')->nullable()->after('icon');
            $table->boolean('is_active')->default(true)->after('required_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn(['requirements_text', 'requirements', 'badge_image', 'is_active']);
        });
    }
};
