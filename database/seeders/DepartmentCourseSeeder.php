<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentCourseSeeder extends Seeder
{
    public function run(): void
    {
        $itDept = Department::firstOrCreate(
            ['code' => 'IT'],
            ['name' => 'Information Technology Department']
        );

        Course::firstOrCreate(
            ['code' => 'BSIT'],
            [
                'department_id' => $itDept->id,
                'name' => 'Bachelor of Science in Information Technology',
            ]
        );

        $csDept = Department::firstOrCreate(
            ['code' => 'CS'],
            ['name' => 'Computer Science Department']
        );

        Course::firstOrCreate(
            ['code' => 'BSCS'],
            [
                'department_id' => $csDept->id,
                'name' => 'Bachelor of Science in Computer Science',
            ]
        );
    }
}
