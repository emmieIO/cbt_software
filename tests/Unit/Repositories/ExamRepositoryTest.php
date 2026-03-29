<?php

namespace Tests\Unit\Repositories;

use App\Models\Exam;
use App\Models\User;
use App\Repositories\Eloquent\EloquentExamRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected EloquentExamRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentExamRepository;
    }

    public function test_can_assign_student_to_exam(): void
    {
        $exam = Exam::factory()->create();
        $user = User::factory()->create();

        $this->repository->assignStudent($exam->id, $user->id);

        $this->assertCount(1, $exam->fresh()->users);
        $this->assertEquals($user->id, $exam->fresh()->users->first()->id);
    }

    public function test_can_remove_student_from_exam(): void
    {
        $exam = Exam::factory()->create();
        $user = User::factory()->create();

        $exam->users()->attach($user->id);
        $this->assertCount(1, $exam->fresh()->users);

        $this->repository->removeStudent($exam->id, $user->id);

        $this->assertCount(0, $exam->fresh()->users);
    }

    public function test_can_get_exams_for_student(): void
    {
        $user = User::factory()->create();
        $exams = Exam::factory()->count(3)->create(['status' => 'live']);

        foreach ($exams as $exam) {
            $exam->users()->attach($user->id);
        }

        // Create one exam not assigned to student
        Exam::factory()->create(['status' => 'live']);

        $results = $this->repository->getExamsForStudent($user->id);

        $this->assertCount(3, $results);
    }
}
