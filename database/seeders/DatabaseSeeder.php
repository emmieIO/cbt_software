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

        // Meaningful Subjects and Topics
        $curriculum = [
            'Mathematics' => [
                'Numbers & Numeration',
                'Algebraic Processes',
                'Mensuration',
                'Plane Geometry',
                'Trigonometry',
                'Statistics & Probability',
            ],
            'English Language' => [
                'Lexis & Structure',
                'Reading Comprehension',
                'Essay Writing',
                'Summary Writing',
                'Oral English',
            ],
            'Physics' => [
                'Measurement & Units',
                'Kinematics & Dynamics',
                'Heat & Temperature',
                'Waves & Optics',
                'Electricity & Magnetism',
                'Atomic & Nuclear Physics',
            ],
            'Chemistry' => [
                'Structure of Atom',
                'Periodic Table & periodicity',
                'Chemical Reactions & Bonding',
                'Stoichiometry',
                'States of Matter',
                'Organic Chemistry',
            ],
            'Biology' => [
                'Cell Structure & Functions',
                'Nutrition & Digestive Systems',
                'Respiratory Systems',
                'Genetics & Heredity',
                'Ecology & Environment',
            ],
            'Economics' => [
                'Basic Concepts of Economics',
                'Theory of Demand & Supply',
                'Production Theory',
                'National Income Accounting',
                'Money & Banking',
                'International Trade',
            ],
            'Computer Science' => [
                'Introduction to Computing',
                'Data Representation',
                'Algorithms & Flowcharts',
                'Programming Fundamentals',
                'Networking & Internet',
                'Database Management Systems',
            ],
            'Civic Education' => [
                'National Values',
                'Human Rights',
                'Structure of Government',
                'Political Participation',
                'Global Interaction',
            ],
        ];

        foreach ($curriculum as $subjectName => $topics) {
            $subject = Subject::create([
                'name' => $subjectName,
                'slug' => Str::slug($subjectName),
            ]);

            foreach ($topics as $topicName) {
                Topic::create([
                    'subject_id' => $subject->id,
                    'name' => $topicName,
                    'slug' => Str::slug($topicName),
                ]);
            }
        }

        // Create some sample questions for SS 1 Mathematics
        $math = Subject::where('name', 'Mathematics')->first();
        $ss1 = SchoolClass::where('name', 'SS 1')->first();
        $algebra = $math->topics()->where('name', 'Algebraic Processes')->first();

        $sampleQuestions = [
            [
                'content' => 'Solve for x: 2x + 5 = 15',
                'explanation' => 'Subtract 5 from both sides: 2x = 10. Then divide by 2: x = 5.',
                'options' => [
                    ['content' => '5', 'is_correct' => true],
                    ['content' => '10', 'is_correct' => false],
                    ['content' => '7.5', 'is_correct' => false],
                    ['content' => '20', 'is_correct' => false],
                ],
            ],
            [
                'content' => 'Factorize completely: x² - 9',
                'explanation' => 'This is a difference of two squares: (x - 3)(x + 3).',
                'options' => [
                    ['content' => '(x - 3)(x + 3)', 'is_correct' => true],
                    ['content' => '(x - 3)²', 'is_correct' => false],
                    ['content' => '(x + 3)²', 'is_correct' => false],
                    ['content' => 'x(x - 9)', 'is_correct' => false],
                ],
            ],
            [
                'content' => 'The sum of the angles in a triangle is 180 degrees. True or False?',
                'explanation' => 'In Euclidean geometry, the sum of internal angles of any triangle is always 180 degrees.',
                'options' => [
                    ['content' => 'True', 'is_correct' => true],
                    ['content' => 'False', 'is_correct' => false],
                ],
            ],
        ];

        foreach ($sampleQuestions as $qData) {
            $question = Question::create([
                'topic_id' => $algebra->id,
                'school_class_id' => $ss1->id,
                'content' => $qData['content'],
                'explanation' => $qData['explanation'],
                'type' => count($qData['options']) === 2 ? 'true_false' : 'multiple_choice',
                'difficulty' => 'medium',
                'created_by' => $staff->id,
            ]);

            foreach ($qData['options'] as $oData) {
                Option::create([
                    'question_id' => $question->id,
                    'content' => $oData['content'],
                    'is_correct' => $oData['is_correct'],
                ]);
            }
        }
    }
}
