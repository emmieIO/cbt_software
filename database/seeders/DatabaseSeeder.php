<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'name' => 'System Admin',
            'username' => 'admin_root',
            'email' => 'admin@chrisland.org',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $staff = User::factory()->create([
            'name' => 'Teacher Staff',
            'username' => 'STAFF/2026/001',
            'email' => 'staff@chrisland.org',
            'password' => bcrypt('password'),
        ]);
        $staff->assignRole('staff');

        $student = User::factory()->create([
            'name' => 'John Student',
            'username' => 'CHS/2026/001',
            'email' => 'student@chrisland.org',
            'password' => bcrypt('password'),
        ]);
        $student->assignRole('student');
    }
}
