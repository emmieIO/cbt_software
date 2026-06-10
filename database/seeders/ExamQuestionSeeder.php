<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->first()
            ?? User::query()->first()
            ?? User::query()->create([
                'name' => 'Seed Question Uploader',
                'username' => 'seed-uploader',
                'email' => 'seed-uploader@chrisland.org',
                'password' => bcrypt('password'),
                'role' => User::ROLE_UPLOADER,
                'permissions' => [User::PERMISSION_CREATE_QUESTIONS, User::PERMISSION_EDIT_QUESTIONS],
            ]);

        DB::transaction(function () use ($creator): void {
            foreach ($this->subjects() as $subjectData) {
                $subject = Subject::query()->updateOrCreate(
                    ['slug' => Str::slug($subjectData['level'].'-'.$subjectData['name'])],
                    [
                        'name' => $subjectData['name'],
                        'description' => $subjectData['description'],
                        'level' => $subjectData['level'],
                    ],
                );

                foreach ($subjectData['topics'] as $topicData) {
                    $topic = Topic::query()->updateOrCreate(
                        ['slug' => Str::slug($subjectData['level'].'-'.$subjectData['name'].'-'.$topicData['name'])],
                        [
                            'subject_id' => $subject->id,
                            'name' => $topicData['name'],
                            'description' => $topicData['description'],
                        ],
                    );

                    foreach ($topicData['questions'] as $questionData) {
                        $question = Question::query()
                            ->where('topic_id', $topic->id)
                            ->whereIn('content', $this->contentAliases($questionData['content']))
                            ->first() ?? new Question(['topic_id' => $topic->id]);

                        $question->fill([
                            'content' => $questionData['content'],
                            'type' => $questionData['type'],
                            'level' => $subjectData['level'],
                            'explanation' => $questionData['explanation'] ?? null,
                            'marking_scheme' => $questionData['marking_scheme'] ?? null,
                            'created_by' => $creator->id,
                        ])->save();

                        if ($questionData['type'] === 'multiple_choice') {
                            $this->syncOptions($question, $questionData['options']);
                        }
                    }
                }
            }
        });
    }

    private function syncOptions(Question $question, array $options): void
    {
        foreach ($options as $index => $optionData) {
            $option = Option::query()
                ->where('question_id', $question->id)
                ->whereIn('content', $this->contentAliases($optionData['content']))
                ->first() ?? new Option(['question_id' => $question->id]);

            $option->fill([
                'content' => $optionData['content'],
                'is_correct' => (bool) ($optionData['is_correct'] ?? false),
            ])->save();
        }

        $validContents = collect($options)->pluck('content')->all();

        Option::query()
            ->where('question_id', $question->id)
            ->whereNotIn('content', $validContents)
            ->delete();
    }

    /**
     * @return array<int, string>
     */
    private function contentAliases(string $content): array
    {
        $legacy = preg_replace_callback(
            '/\$(?!\d+\$)(.+?)\$(?!\d)/s',
            fn (array $matches) => '\\('.$matches[1].'\\)',
            $content,
        ) ?? $content;

        return array_values(array_unique([$content, $legacy]));
    }

    private function subjects(): array
    {
        return [
            [
                'name' => 'Mathematics',
                'level' => 'js',
                'description' => 'Junior secondary mathematics questions for generated exam papers.',
                'topics' => [
                    [
                        'name' => 'Algebraic Expressions',
                        'description' => 'Simplifying expressions and solving simple equations.',
                        'questions' => [
                            [
                                'type' => 'multiple_choice',
                                'content' => 'Simplify $3x + 2x - x$.',
                                'explanation' => 'Combine like terms: $3x + 2x - x = 4x$.',
                                'options' => [
                                    ['content' => '$4x$', 'is_correct' => true],
                                    ['content' => '$5x$'],
                                    ['content' => '$6x$'],
                                    ['content' => '$x$'],
                                ],
                            ],
                            [
                                'type' => 'multiple_choice',
                                'content' => 'If $2y + 5 = 17$, find $y$.',
                                'explanation' => '$2y = 12$, so $y = 6$.',
                                'options' => [
                                    ['content' => '4'],
                                    ['content' => '5'],
                                    ['content' => '6', 'is_correct' => true],
                                    ['content' => '12'],
                                ],
                            ],
                            [
                                'type' => 'short_answer',
                                'content' => 'Expand $4(a + 3)$.',
                                'marking_scheme' => [
                                    ['point' => 'Applies distributive law correctly', 'weight' => 1],
                                    ['point' => 'Gives final answer $4a + 12$', 'weight' => 1],
                                ],
                            ],
                            [
                                'type' => 'theory',
                                'content' => 'Solve $3x - 7 = 20$ and show each step.',
                                'marking_scheme' => [
                                    ['point' => 'Adds 7 to both sides to get $3x = 27$', 'weight' => 2],
                                    ['point' => 'Divides both sides by 3', 'weight' => 1],
                                    ['point' => 'States $x = 9$', 'weight' => 1],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Mensuration',
                        'description' => 'Area, perimeter, and volume calculations.',
                        'questions' => [
                            [
                                'type' => 'multiple_choice',
                                'content' => 'Find the area of a rectangle with length 12 cm and breadth 5 cm.',
                                'explanation' => '$Area = length \times breadth = 12 \times 5 = 60\text{ cm}^2$.',
                                'options' => [
                                    ['content' => '17 cm²'],
                                    ['content' => '34 cm²'],
                                    ['content' => '60 cm²', 'is_correct' => true],
                                    ['content' => '120 cm²'],
                                ],
                            ],
                            [
                                'type' => 'multiple_choice',
                                'content' => 'The perimeter of a square is 36 cm. What is the length of one side?',
                                'explanation' => '$Side\ length = 36 \div 4 = 9\text{ cm}$.',
                                'options' => [
                                    ['content' => '6 cm'],
                                    ['content' => '8 cm'],
                                    ['content' => '9 cm', 'is_correct' => true],
                                    ['content' => '12 cm'],
                                ],
                            ],
                            [
                                'type' => 'short_answer',
                                'content' => 'Calculate the circumference of a circle with radius 7 cm. Use $\pi = \frac{22}{7}$.',
                                'marking_scheme' => [
                                    ['point' => 'Uses $2\pi r$', 'weight' => 1],
                                    ['point' => 'Substitutes values correctly', 'weight' => 1],
                                    ['point' => 'Gets 44 cm', 'weight' => 1],
                                ],
                            ],
                            [
                                'type' => 'theory',
                                'content' => 'A rectangular garden is 18 m long and 10 m wide. Find its perimeter and area.',
                                'marking_scheme' => [
                                    ['point' => 'Correct perimeter formula and substitution', 'weight' => 2],
                                    ['point' => 'Correct area formula and substitution', 'weight' => 2],
                                    ['point' => 'States perimeter as 56 m and area as 180 m²', 'weight' => 2],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Physics',
                'level' => 'ss',
                'description' => 'Senior secondary physics questions for realistic exam generation.',
                'topics' => [
                    [
                        'name' => 'Mechanics',
                        'description' => 'Motion, force, work, energy, and momentum.',
                        'questions' => [
                            [
                                'type' => 'multiple_choice',
                                'content' => 'Which physical quantity is defined as the rate of change of velocity?',
                                'explanation' => 'Acceleration is change in velocity per unit time.',
                                'options' => [
                                    ['content' => 'Speed'],
                                    ['content' => 'Acceleration', 'is_correct' => true],
                                    ['content' => 'Momentum'],
                                    ['content' => 'Work'],
                                ],
                            ],
                            [
                                'type' => 'multiple_choice',
                                'content' => 'A body of mass 4 kg accelerates at $3\text{ m/s}^2$. Find the force acting on it.',
                                'explanation' => '$F = ma = 4 \times 3 = 12\text{ N}$.',
                                'options' => [
                                    ['content' => '7 N'],
                                    ['content' => '12 N', 'is_correct' => true],
                                    ['content' => '24 N'],
                                    ['content' => '36 N'],
                                ],
                            ],
                            [
                                'type' => 'short_answer',
                                'content' => 'State Newton\'s first law of motion.',
                                'marking_scheme' => [
                                    ['point' => 'Mentions rest or uniform motion in a straight line', 'weight' => 2],
                                    ['point' => 'Mentions no resultant external force', 'weight' => 1],
                                ],
                            ],
                            [
                                'type' => 'theory',
                                'content' => 'A car accelerates uniformly from rest to $20\text{ m/s}$ in 5 seconds. Calculate its acceleration and distance covered.',
                                'marking_scheme' => [
                                    ['point' => 'Uses $a = \frac{v-u}{t}$', 'weight' => 2],
                                    ['point' => 'Gets acceleration as $4\text{ m/s}^2$', 'weight' => 1],
                                    ['point' => 'Uses correct distance equation', 'weight' => 2],
                                    ['point' => 'Gets distance as 50 m', 'weight' => 1],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Electricity',
                        'description' => 'Current, voltage, resistance, and electrical power.',
                        'questions' => [
                            [
                                'type' => 'multiple_choice',
                                'content' => 'The SI unit of electric current is the',
                                'explanation' => 'Electric current is measured in amperes.',
                                'options' => [
                                    ['content' => 'Volt'],
                                    ['content' => 'Ohm'],
                                    ['content' => 'Ampere', 'is_correct' => true],
                                    ['content' => 'Watt'],
                                ],
                            ],
                            [
                                'type' => 'multiple_choice',
                                'content' => 'A resistor has resistance $6\Omega$ and current 2 A. What is the potential difference across it?',
                                'explanation' => '$V = IR = 2 \times 6 = 12\text{ V}$.',
                                'options' => [
                                    ['content' => '3 V'],
                                    ['content' => '8 V'],
                                    ['content' => '12 V', 'is_correct' => true],
                                    ['content' => '24 V'],
                                ],
                            ],
                            [
                                'type' => 'short_answer',
                                'content' => 'Define electrical resistance.',
                                'marking_scheme' => [
                                    ['point' => 'Describes opposition to flow of current', 'weight' => 2],
                                    ['point' => 'Mentions unit as ohm where appropriate', 'weight' => 1],
                                ],
                            ],
                            [
                                'type' => 'theory',
                                'content' => 'Explain two safety precautions that should be observed when using electrical appliances.',
                                'marking_scheme' => [
                                    ['point' => 'Gives first valid precaution with explanation', 'weight' => 2],
                                    ['point' => 'Gives second valid precaution with explanation', 'weight' => 2],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'English Language',
                'level' => 'js',
                'description' => 'Junior secondary English language questions for grammar and comprehension exams.',
                'topics' => [
                    [
                        'name' => 'Grammar and Usage',
                        'description' => 'Parts of speech, agreement, tense, and sentence correction.',
                        'questions' => [
                            [
                                'type' => 'multiple_choice',
                                'content' => 'Choose the correct verb: The list of items ___ on the table.',
                                'explanation' => 'The subject is "list", which is singular.',
                                'options' => [
                                    ['content' => 'are'],
                                    ['content' => 'were'],
                                    ['content' => 'is', 'is_correct' => true],
                                    ['content' => 'have been'],
                                ],
                            ],
                            [
                                'type' => 'multiple_choice',
                                'content' => 'Identify the adverb in the sentence: She answered the question confidently.',
                                'explanation' => 'Confidently describes how she answered.',
                                'options' => [
                                    ['content' => 'She'],
                                    ['content' => 'answered'],
                                    ['content' => 'question'],
                                    ['content' => 'confidently', 'is_correct' => true],
                                ],
                            ],
                            [
                                'type' => 'short_answer',
                                'content' => 'Rewrite this sentence in the past tense: "The students write neatly."',
                                'marking_scheme' => [
                                    ['point' => 'Changes write to wrote', 'weight' => 1],
                                    ['point' => 'Keeps sentence clear and grammatical', 'weight' => 1],
                                ],
                            ],
                            [
                                'type' => 'theory',
                                'content' => 'Write a short paragraph describing your school library. Use at least five descriptive words.',
                                'marking_scheme' => [
                                    ['point' => 'Paragraph is coherent and relevant', 'weight' => 2],
                                    ['point' => 'Uses at least five descriptive words', 'weight' => 2],
                                    ['point' => 'Uses correct grammar and punctuation', 'weight' => 2],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Comprehension',
                        'description' => 'Reading passages, inference, and vocabulary in context.',
                        'questions' => [
                            [
                                'type' => 'multiple_choice',
                                'content' => 'In comprehension passages, the "main idea" means the',
                                'explanation' => 'The main idea is the central point of a passage.',
                                'options' => [
                                    ['content' => 'longest sentence'],
                                    ['content' => 'central message', 'is_correct' => true],
                                    ['content' => 'first word'],
                                    ['content' => 'name of the writer'],
                                ],
                            ],
                            [
                                'type' => 'multiple_choice',
                                'content' => 'A word that has nearly the same meaning as another word is called a',
                                'explanation' => 'A synonym is a word with a similar meaning.',
                                'options' => [
                                    ['content' => 'synonym', 'is_correct' => true],
                                    ['content' => 'pronoun'],
                                    ['content' => 'prefix'],
                                    ['content' => 'clause'],
                                ],
                            ],
                            [
                                'type' => 'short_answer',
                                'content' => 'What should a reader do first before answering comprehension questions?',
                                'marking_scheme' => [
                                    ['point' => 'Read the passage carefully', 'weight' => 1],
                                    ['point' => 'Understands or identifies key details before answering', 'weight' => 1],
                                ],
                            ],
                            [
                                'type' => 'theory',
                                'content' => 'Read a short passage carefully and explain how context clues can help you find the meaning of an unfamiliar word.',
                                'marking_scheme' => [
                                    ['point' => 'Explains context clues correctly', 'weight' => 2],
                                    ['point' => 'Mentions surrounding words or sentences', 'weight' => 2],
                                    ['point' => 'Gives a relevant example', 'weight' => 2],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
