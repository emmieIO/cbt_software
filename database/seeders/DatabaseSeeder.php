<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // Create Admin
        $admin = User::factory()->create([
            'name' => 'System Admin',
            'username' => 'admin_root',
            'email' => 'admin@chrisland.org',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        // Create Staff
        $staff = User::factory()->create([
            'name' => 'Teacher Staff',
            'username' => 'STAFF/2026/001',
            'email' => 'staff@chrisland.org',
            'password' => bcrypt('password'),
        ]);
        $staff->assignRole('staff');

        // Create Student
        $student = User::factory()->create([
            'name' => 'John Student',
            'username' => 'CHS/2026/001',
            'email' => 'student@chrisland.org',
            'password' => bcrypt('password'),
        ]);
        $student->assignRole('student');

        // Create Classes
        $classes = [
            ['name' => 'Primary 1', 'level' => 'primary'],
            ['name' => 'Primary 2', 'level' => 'primary'],
            ['name' => 'Primary 3', 'level' => 'primary'],
            ['name' => 'Primary 4', 'level' => 'primary'],
            ['name' => 'Primary 5', 'level' => 'primary'],
            ['name' => 'Primary 6', 'level' => 'primary'],
            ['name' => 'JSS 1', 'level' => 'secondary'],
            ['name' => 'JSS 2', 'level' => 'secondary'],
            ['name' => 'JSS 3', 'level' => 'secondary'],
            ['name' => 'SS 1', 'level' => 'secondary'],
            ['name' => 'SS 2', 'level' => 'secondary'],
            ['name' => 'SS 3', 'level' => 'secondary'],
        ];

        foreach ($classes as $class) {
            SchoolClass::create([
                'name' => $class['name'],
                'slug' => Str::slug($class['name']),
                'level' => $class['level'],
            ]);
        }

        // Create Subjects
        $subjects = [
            'Mathematics',
            'English Language',
            'Physics',
            'Chemistry',
            'Biology',
            'Economics',
            'Civic Education',
            'Computer Science',
        ];

        foreach ($subjects as $subjectName) {
            $subject = Subject::create([
                'name' => $subjectName,
                'slug' => Str::slug($subjectName),
            ]);

            // Create Topics for each subject
            for ($i = 1; $i <= 5; $i++) {
                Topic::create([
                    'subject_id' => $subject->id,
                    'name' => "Topic $i for $subjectName",
                    'slug' => Str::slug("Topic $i for $subjectName"),
                ]);
            }
        }

        // Create some sample questions for the first topic of Mathematics for SS 1
        $math = Subject::where('name', 'Mathematics')->first();
        $ss1 = SchoolClass::where('name', 'SS 1')->first();
        $topic = $math->topics()->first();

        for ($i = 1; $i <= 10; $i++) {
            $question = Question::create([
                'topic_id' => $topic->id,
                'school_class_id' => $ss1->id,
                'content' => "Sample Mathematics Question $i for SS 1?",
                'explanation' => "This is the explanation for question $i.",
                'type' => 'multiple_choice',
                'difficulty' => ['easy', 'medium', 'hard'][rand(0, 2)],
                'created_by' => $staff->id,
            ]);

            // Create Options
            for ($j = 1; $j <= 4; $j++) {
                Option::create([
                    'question_id' => $question->id,
                    'content' => "Option $j for question $i",
                    'is_correct' => $j === 1, // First option is correct
                ]);
            }
        }
    }
}
