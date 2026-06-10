<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ExamTitle;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->count('*') === 0) {
            User::create([
                'name' => 'System Administrator',
                'username' => 'admin',
                'email' => 'admin@chrisland.org',
                'password' => bcrypt('password'),
                'role' => User::ROLE_ADMIN,
            ]);

            User::create([
                'name' => 'Question Uploader',
                'username' => 'uploader',
                'email' => 'uploader@chrisland.org',
                'password' => bcrypt('password'),
                'role' => User::ROLE_UPLOADER,
            ]);
        }

        foreach ([
            'First Term Examination',
            'Second Term Examination',
            'Third Term Examination',
            'Mid-Term Examination',
            'Mock Examination',
            'Entrance Examination',
        ] as $title) {
            ExamTitle::query()->firstOrCreate(['name' => $title], ['is_active' => true]);
        }

        $this->call(ExamQuestionSeeder::class);
    }
}
