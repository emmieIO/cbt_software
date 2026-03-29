<?php

namespace Database\Seeders;

use App\Enums\AttemptStatus;
use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class DummyResultsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@chrisland.org')->first();
        $staff = User::where('email', 'staff@chrisland.org')->first();
        $session = AcademicSession::firstOrCreate(
            ['is_current' => true],
            [
                'name' => '2025/2026',
                'term' => 'first',
                'start_date' => now(),
                'end_date' => now()->addMonths(4),
            ]
        );

        // 1. Create 50 Mock Candidates
        $candidates = collect();
        for ($i = 1; $i <= 50; $i++) {
            $student = User::firstOrCreate(
                ['username' => 'CAND/2026/'.str_pad($i, 3, '0', STR_PAD_LEFT)],
                [
                    'name' => 'Mock Candidate '.$i,
                    'email' => 'candidate'.$i.'@chrisland.org',
                    'password' => bcrypt('password'),
                ]
            );
            $student->assignRole('candidate');
            $candidates->push($student);
        }

        // 2. Locate Mathematics (Secondary) and SS1
        $ss1 = SchoolClass::where('name', 'SS 1')->first();
        $math = Subject::where('name', 'Mathematics')->where('level', 'secondary')->first();
        $topic = $math->topics()->where('name', 'Quadratic Equations')->first();

        // Ensure we have at least 10 questions for a solid exam layout
        $existingQuestionsCount = Question::where('school_class_id', $ss1->id)
            ->where('topic_id', $topic->id)
            ->count();

        if ($existingQuestionsCount < 10) {
            for ($q = $existingQuestionsCount + 1; $q <= 10; $q++) {
                $qst = Question::create([
                    'topic_id' => $topic->id,
                    'school_class_id' => $ss1->id,
                    'content' => 'Simulated Math Question '.$q.' for result layout testing. What is the correct answer?',
                    'type' => 'multiple_choice',
                    'difficulty' => collect(['easy', 'medium', 'hard'])->random(),
                    'created_by' => $staff->id,
                ]);

                $qst->options()->create(['content' => 'The Correct Simulated Answer', 'is_correct' => true]);
                $qst->options()->create(['content' => 'A Plausible Distractor', 'is_correct' => false]);
                $qst->options()->create(['content' => 'An Obvious Wrong Answer', 'is_correct' => false]);
                $qst->options()->create(['content' => 'Another Distractor', 'is_correct' => false]);
            }
        }

        // 3. Create the Exam
        $exam = Exam::create([
            'title' => 'First Term Mathematics Mock Examination',
            'subject_id' => $math->id,
            'school_class_id' => $ss1->id,
            'academic_session_id' => $session->id ?? null,
            'created_by' => $admin->id,
            'duration' => 60,
            'type' => ExamType::TERMINAL,
            'status' => ExamStatus::LIVE,
            'settings' => [
                'show_results_after' => true,
                'shuffle_questions' => true,
            ],
        ]);

        // 4. Bind Questions to Exam
        $questions = Question::where('school_class_id', $ss1->id)
            ->where('topic_id', $topic->id)
            ->take(10)
            ->get();

        foreach ($questions as $index => $q) {
            $exam->questions()->attach($q->id, ['marks' => 10, 'order' => $index + 1]);
        }
        $totalExamMarks = $questions->count() * 10;

        // 5. Generate Attempts and Answers for Candidates
        foreach ($candidates as $candidate) {
            // Register candidate to the exam
            $exam->users()->attach($candidate->id);

            // Determine if passed, failed, or average
            $proficiency = rand(1, 100);
            $correctChance = $proficiency > 80 ? 90 : ($proficiency > 40 ? 60 : 30);

            $attempt = ExamAttempt::create([
                'exam_id' => $exam->id,
                'user_id' => $candidate->id,
                'started_at' => now()->subMinutes(60),
                'submitted_at' => now()->subMinutes(rand(1, 15)),
                'status' => AttemptStatus::GRADED,
                'score' => 0,
            ]);

            $totalScore = 0;
            foreach ($questions as $q) {
                $isCorrect = rand(1, 100) <= $correctChance;

                if ($isCorrect) {
                    $selectedOption = $q->options()->where('is_correct', true)->first();
                    $score = 10; // Matches marks allocated
                } else {
                    $selectedOption = $q->options()->where('is_correct', false)->inRandomOrder()->first();
                    $score = 0;
                }

                ExamAnswer::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $q->id,
                    'selected_options' => $selectedOption ? [$selectedOption->id] : [],
                    'is_correct' => $isCorrect,
                    'score' => $score,
                ]);

                $totalScore += $score;
            }

            $attempt->update(['score' => $totalScore]);
        }
    }
}
