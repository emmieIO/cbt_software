<?php

namespace App\Services;

use App\DTOs\ExamDTO;
use App\DTOs\ExamVersionDTO;
use App\Models\Exam;
use App\Models\ExamVersion;
use Illuminate\Database\Eloquent\Collection;

interface ExamServiceInterface
{
    public function createExam(ExamDTO $dto, string $creatorId): Exam;

    public function createVersion(Exam $exam, ExamVersionDTO $dto): ExamVersion;

    public function deleteVersion(ExamVersion $version): void;

    public function updateVersionQuestions(ExamVersion $version, array $questionIds): void;

    public function autoSelectQuestions(Exam $exam, ExamVersion $version, int $count): int;

    public function getAvailableQuestions(Exam $exam): Collection;
}
