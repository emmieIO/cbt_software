<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ComprehensiveTopicSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = Subject::all()->pluck('id', 'name');
        $classes = SchoolClass::all()->pluck('id', 'name');

        $data = [
            'Mathematics' => [
                'Primary 1' => ['Counting 1-100', 'Basic Addition', 'Basic Subtraction', 'Simple Shapes'],
                'Primary 2' => ['Multiplication Tables', 'Division Basics', 'Fractions', 'Length and Weight'],
                'Primary 3' => ['Decimals', 'Area and Perimeter', 'Data Presentation', 'Roman Numerals'],
                'JSS 1' => ['Number Bases', 'Fractions and Percentages', 'Algebraic Simplification', 'Plane Geometry'],
                'JSS 2' => ['Direct and Inverse Proportion', 'Linear Equations', 'Pythagoras Theorem', 'Probability'],
                'JSS 3' => ['Quadratic Equations Basics', 'Trigonometric Ratios', 'Mensuration', 'Statistics'],
                'SS 1' => ['Number Bases (Advanced)', 'Logarithms', 'Sets', 'Surds'],
                'SS 2' => ['Quadratic Equations', 'Sequence and Series', 'Circle Geometry', 'Trigonometry'],
                'SS 3' => ['Calculus (Differentiation)', 'Calculus (Integration)', 'Matrices and Determinants', 'Vectors'],
            ],
            'English Language' => [
                'Primary 1' => ['Alphabet Sounds', 'Nouns Introduction', 'Simple Verbs', 'Sentence Building'],
                'JSS 1' => ['Parts of Speech', 'Reading Comprehension', 'Informal Letter Writing', 'Punctuation Marks'],
                'SS 1' => ['Clauses and Phrases', 'Formal Letter Writing', 'Narrative Essays', 'Oral English (Vowels)'],
                'SS 2' => ['Concord', 'Summary Writing', 'Argumentative Essays', 'Figures of Speech'],
            ],
            'Physics' => [
                'SS 1' => ['Units and Dimensions', 'Motion in a Straight Line', 'Heat Energy', 'Light Waves'],
                'SS 2' => ['Projectile Motion', 'Equilibrium of Forces', 'Sound Waves', 'Current Electricity'],
                'SS 3' => ['Electromagnetic Induction', 'Atomic Physics', 'Quantum Physics', 'Radioactivity'],
            ],
            'Chemistry' => [
                'SS 1' => ['Introduction to Chemistry', 'Atomic Structure', 'Periodic Table', 'Chemical Formulas'],
                'SS 2' => ['Chemical Bonding', 'Stoichiometry', 'Gas Laws', 'Acids, Bases and Salts'],
                'SS 3' => ['Organic Chemistry (Hydrocarbons)', 'Electrochemistry', 'Chemical Equilibrium', 'Thermochemistry'],
            ],
            'Biology' => [
                'SS 1' => ['Classification of Living Things', 'The Cell', 'Living and Non-Living Things', 'Kingdom Monera'],
                'SS 2' => ['Digestive System', 'Circulatory System', 'Excretory System', 'Plant Nutrition'],
                'SS 3' => ['Genetics', 'Evolution', 'Nervous System', 'Reproductive System'],
            ],
            'Computer Science' => [
                'JSS 1' => ['Introduction to Computers', 'History of Computing', 'Hardware and Software', 'Word Processing'],
                'SS 1' => ['Algorithms and Flowcharts', 'Computer Ethics', 'Programming in BASIC', 'Number Systems'],
                'SS 2' => ['Operating Systems', 'System Development Cycle', 'Computer Networking', 'Database Management'],
            ],
            'Social Studies' => [
                'JSS 1' => ['Family and Marriage', 'Socialization', 'Culture and Social Identity', 'National Identity'],
                'JSS 2' => ['Social Issues', 'Human Rights', 'Leadership and Followership', 'Resource Management'],
            ],
            'Basic Science' => [
                'JSS 1' => ['Living and Non-Living Things', 'The Human Body', 'Measurement', 'Energy Basics'],
                'JSS 2' => ['Matter', 'Chemical Reactions Basics', 'Thermal Energy', 'Simple Machines'],
                'JSS 3' => ['Earth and Space', 'Electricity Basics', 'Atomic Structure (Intro)', 'Environment Protection'],
            ],
        ];

        foreach ($data as $subjectName => $classTopics) {
            if (! isset($subjects[$subjectName])) {
                continue;
            }

            $subjectId = $subjects[$subjectName];

            foreach ($classTopics as $className => $topics) {
                if (! isset($classes[$className])) {
                    continue;
                }

                $classId = $classes[$className];

                foreach ($topics as $topicName) {
                    Topic::updateOrCreate(
                        [
                            'name' => $topicName,
                            'subject_id' => $subjectId,
                            'school_class_id' => $classId,
                        ],
                        [
                            'slug' => Str::slug($topicName.'-'.$className),
                            'description' => "Comprehensive topic on $topicName for $className $subjectName.",
                        ]
                    );
                }
            }
        }
    }
}
