<?php

namespace Database\Factories;

use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Models\AcademicSession;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exam>
 */
class ExamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'school_id' => School::factory(),
            'subject_id' => Subject::factory(),
            'school_class_id' => SchoolClass::factory(),
            'academic_session_id' => AcademicSession::factory(),
            'created_by' => User::factory(),
            'duration' => 60,
            'type' => ExamType::CA,
            'status' => ExamStatus::DRAFT,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
        ];
    }
}
