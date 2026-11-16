<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(BadgeAchievementSeeder::class);

        // 1. Create Default Department
        $dept = Department::firstOrCreate(
            ['name' => 'Information Technology'],
            ['code' => 'IT']
        );

        // 2. Create Super-Admin Account
        User::firstOrCreate(
            ['email' => 'superadmin@lemu.test'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password123'),
                'role' => 'super-admin',
                'department_id' => null,
            ]
        );

        // 3. Create Department Admin Account
        User::firstOrCreate(
            ['email' => 'admin@lemu.test'],
            [
                'name' => 'IT Department Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'department_id' => $dept->id,
            ]
        );

        // 4. Create Student Account
        User::firstOrCreate(
            ['email' => 'student@lemu.test'],
            [
                'name' => 'IT Student',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'department_id' => $dept->id,
            ]
        );
    }
}
