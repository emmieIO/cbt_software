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
        $classDefinitions = [
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

        $classes = collect();
        foreach ($classDefinitions as $class) {
            $classes->push(SchoolClass::create([
                'name' => $class['name'],
                'slug' => Str::slug($class['name']),
                'level' => $class['level'],
            ]));
        }

        $p1 = $classes->where('name', 'Primary 1')->first();
        $p2 = $classes->where('name', 'Primary 2')->first();
        $p3 = $classes->where('name', 'Primary 3')->first();
        $p4 = $classes->where('name', 'Primary 4')->first();
        $p5 = $classes->where('name', 'Primary 5')->first();
        $p6 = $classes->where('name', 'Primary 6')->first();
        $js1 = $classes->where('name', 'JSS 1')->first();
        $js2 = $classes->where('name', 'JSS 2')->first();
        $js3 = $classes->where('name', 'JSS 3')->first();
        $ss1 = $classes->where('name', 'SS 1')->first();
        $ss2 = $classes->where('name', 'SS 2')->first();
        $ss3 = $classes->where('name', 'SS 3')->first();

        // Comprehensive Curriculum
        $curriculum = [
            'Mathematics' => [
                // Primary
                ['name' => 'Numbers 1-100', 'class' => $p1],
                ['name' => 'Basic Addition & Subtraction', 'class' => $p1],
                ['name' => 'Multiplication Tables', 'class' => $p2],
                ['name' => 'Fractions Intro', 'class' => $p3],
                ['name' => 'Decimals & Percentages', 'class' => $p4],
                ['name' => 'Prime Numbers', 'class' => $p5],
                ['name' => 'Ratio & Proportion', 'class' => $p6],
                // Junior Secondary
                ['name' => 'Number Bases', 'class' => $js1],
                ['name' => 'Algebraic Simplification', 'class' => $js1],
                ['name' => 'Linear Equations', 'class' => $js2],
                ['name' => 'Pythagoras Theorem', 'class' => $js3],
                // Senior Secondary
                ['name' => 'Logarithms', 'class' => $ss1],
                ['name' => 'Quadratic Equations', 'class' => $ss1],
                ['name' => 'Circle Geometry', 'class' => $ss2],
                ['name' => 'Trigonometry II', 'class' => $ss2],
                ['name' => 'Calculus Intro', 'class' => $ss3],
                ['name' => 'Statistics & Probability', 'class' => $ss3],
            ],
            'English Language' => [
                ['name' => 'Alphabets & Sounds', 'class' => $p1],
                ['name' => 'Noun & Verbs', 'class' => $p2],
                ['name' => 'Parts of Speech', 'class' => $p4],
                ['name' => 'Tenses & Concord', 'class' => $js1],
                ['name' => 'Lexis & Structure', 'class' => $ss1],
                ['name' => 'Reading Comprehension', 'class' => $ss1],
                ['name' => 'Oral English', 'class' => $ss3],
            ],
            'Physics' => [
                ['name' => 'Measurement & Units', 'class' => $ss1],
                ['name' => 'Kinematics', 'class' => $ss1],
                ['name' => 'Heat & Temperature', 'class' => $ss2],
                ['name' => 'Waves & Optics', 'class' => $ss2],
                ['name' => 'Electricity', 'class' => $ss3],
                ['name' => 'Atomic Physics', 'class' => $ss3],
            ],
            'Chemistry' => [
                ['name' => 'Matter & Nature', 'class' => $ss1],
                ['name' => 'Atomic Structure', 'class' => $ss1],
                ['name' => 'Chemical Bonding', 'class' => $ss2],
                ['name' => 'Organic Chemistry I', 'class' => $ss2],
                ['name' => 'Organic Chemistry II', 'class' => $ss3],
                ['name' => 'Stoichiometry', 'class' => $ss3],
            ],
            'Biology' => [
                ['name' => 'Living Things', 'class' => $p1],
                ['name' => 'The Human Body', 'class' => $js1],
                ['name' => 'Cell Structure', 'class' => $ss1],
                ['name' => 'Plant & Animal Nutrition', 'class' => $ss1],
                ['name' => 'Reproductive Systems', 'class' => $ss2],
                ['name' => 'Genetics', 'class' => $ss3],
            ],
            'Computer Science' => [
                ['name' => 'Intro to Computers', 'class' => $p4],
                ['name' => 'Computer Hardware', 'class' => $js1],
                ['name' => 'Software Concepts', 'class' => $js2],
                ['name' => 'Algorithms', 'class' => $ss1],
                ['name' => 'Programming in Python', 'class' => $ss2],
                ['name' => 'Database Systems', 'class' => $ss3],
            ],
            'Social Studies' => [
                ['name' => 'My Family', 'class' => $p1],
                ['name' => 'My School', 'class' => $p2],
                ['name' => 'The Community', 'class' => $p3],
                ['name' => 'Culture & Traditions', 'class' => $js1],
                ['name' => 'Social Issues', 'class' => $js2],
            ],
            'Basic Science' => [
                ['name' => 'Senses', 'class' => $p1],
                ['name' => 'Plants', 'class' => $p2],
                ['name' => 'Animals', 'class' => $p3],
                ['name' => 'Energy', 'class' => $js1],
                ['name' => 'Matter', 'class' => $js2],
            ],
        ];

        foreach ($curriculum as $subjectName => $topics) {
            $subject = Subject::create([
                'name' => $subjectName,
                'slug' => Str::slug($subjectName),
            ]);

            foreach ($topics as $topicData) {
                Topic::create([
                    'subject_id' => $subject->id,
                    'school_class_id' => $topicData['class']->id,
                    'name' => $topicData['name'],
                    'slug' => Str::slug($topicData['name'] . '-' . $topicData['class']->name),
                ]);
            }
        }

        // Create initial sample questions for Math SS1
        $math = Subject::where('name', 'Mathematics')->first();
        $algebra = $math->topics()->where('name', 'Quadratic Equations')->first();

        if ($algebra && $ss1) {
            $question = Question::create([
                'topic_id' => $algebra->id,
                'school_class_id' => $ss1->id,
                'content' => 'What is the discriminant of the quadratic equation ax² + bx + c = 0?',
                'explanation' => 'The discriminant is the part of the quadratic formula under the square root: b² - 4ac.',
                'type' => 'multiple_choice',
                'difficulty' => 'medium',
                'created_by' => $staff->id,
            ]);

            Option::create(['question_id' => $question->id, 'content' => 'b² - 4ac', 'is_correct' => true]);
            Option::create(['question_id' => $question->id, 'content' => 'b² + 4ac', 'is_correct' => false]);
            Option::create(['question_id' => $question->id, 'content' => '2a \/ -b', 'is_correct' => false]);
            Option::create(['question_id' => $question->id, 'content' => '4ac - b²', 'is_correct' => false]);
        }
    }
}
