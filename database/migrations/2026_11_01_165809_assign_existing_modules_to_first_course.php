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
        $departmentIds = DB::table('courses')
            ->select('department_id')
            ->distinct()
            ->pluck('department_id');

        foreach ($departmentIds as $departmentId) {
            $firstCourseId = DB::table('courses')
                ->where('department_id', $departmentId)
                ->orderBy('id')
                ->value('id');

            DB::table('modules')
                ->where('department_id', $departmentId)
                ->whereNull('course_id')
                ->update(['course_id' => $firstCourseId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Existing module ownership cannot be safely reversed after students use it.
    }
};
