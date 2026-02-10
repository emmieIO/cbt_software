<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamVersion;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CbtDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Academic Session
        $session = AcademicSession::firstOrCreate(
            ['is_current' => true],
            [
                'name' => '2025/2026 Academic Session',
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
            ]
        );

        // 2. Create School Class
        $class = SchoolClass::firstOrCreate(
            ['name' => 'SS 1'],
            ['level' => 'SS 1']
        );

        // 3. Create Subject
        $subject = Subject::firstOrCreate(
            ['name' => 'Physics'],
            ['code' => 'PHY']
        );

        // 4. Create Topic
        $topic = Topic::firstOrCreate(
            ['name' => 'Kinematics', 'subject_id' => $subject->id],
            ['school_class_id' => $class->id]
        );

        // 5. Create Staff (Teacher)
        $staff = User::firstOrCreate(
            ['email' => 'teacher@chrisland.com'],
            [
                'name' => 'John Teacher',
                'username' => 'teacher_john',
                'password' => Hash::make('password'),
            ]
        );
        if (!$staff->hasRole('staff')) {
            $staff->assignRole('staff');
        }

        // 6. Create Student
        $student = User::firstOrCreate(
            ['email' => 'student@chrisland.com'],
            [
                'name' => 'Jane Student',
                'username' => 'student_jane',
                'password' => Hash::make('password'),
                'school_class_id' => $class->id,
            ]
        );
        if (!$student->hasRole('student')) {
            $student->assignRole('student');
        }

        // Enroll student and assign teacher
        \App\Models\ClassEnrollment::firstOrCreate([
            'user_id' => $student->id,
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
        ]);

        \App\Models\TeacherAssignment::firstOrCreate([
            'user_id' => $staff->id,
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
        ]);

        // 7. Create Questions
        $questionsData = [
            [
                'content' => 'What is the SI unit of velocity?',
                'explanation' => 'Velocity is displacement over time, hence m/s.',
                'options' => [
                    ['content' => 'm/s', 'is_correct' => true],
                    ['content' => 'm/s²', 'is_correct' => false],
                    ['content' => 'kg', 'is_correct' => false],
                    ['content' => 'Newton', 'is_correct' => false],
                ],
            ],
            [
                'content' => 'Which of these is a vector quantity?',
                'explanation' => 'Displacement has both magnitude and direction.',
                'options' => [
                    ['content' => 'Displacement', 'is_correct' => true],
                    ['content' => 'Distance', 'is_correct' => false],
                    ['content' => 'Mass', 'is_correct' => false],
                    ['content' => 'Time', 'is_correct' => false],
                ],
            ],
            [
                'content' => 'Acceleration is defined as the rate of change of:',
                'explanation' => 'Acceleration = (Final Velocity - Initial Velocity) / Time.',
                'options' => [
                    ['content' => 'Velocity', 'is_correct' => true],
                    ['content' => 'Distance', 'is_correct' => false],
                    ['content' => 'Mass', 'is_correct' => false],
                    ['content' => 'Speed', 'is_correct' => false],
                ],
            ],
        ];

        foreach ($questionsData as $data) {
            $q = Question::create([
                'topic_id' => $topic->id,
                'school_class_id' => $class->id,
                'content' => $data['content'],
                'explanation' => $data['explanation'],
                'type' => QuestionType::MULTIPLE_CHOICE,
                'difficulty' => QuestionDifficulty::MEDIUM,
                'created_by' => $staff->id,
            ]);

            foreach ($data['options'] as $opt) {
                $q->options()->create($opt);
            }
        }

        // 8. Create Exam
        $exam = Exam::create([
            'title' => 'First Term Physics Quiz',
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
            'academic_session_id' => $session->id,
            'created_by' => $staff->id,
            'duration' => 10,
            'type' => ExamType::CA,
            'status' => ExamStatus::LIVE,
            'start_time' => now(),
            'end_time' => now()->addDays(7),
        ]);

        // 9. Link questions directly to the exam
        $exam->questions()->sync(Question::all()->pluck('id'));
    }
}