<?php

namespace App\Services\Exam;

use App\DTOs\ExamCompositionDTO;
use App\Enums\ExamStatus;
use App\DTOs\ExamDTO;
use App\Models\Exam;
use App\Models\School;
use App\Services\ExamService;
use InvalidArgumentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamManagementService
{
    public function __construct(
        protected ExamService $examService
    ) {}

    /**
     * Create a new exam with normalized branch metadata.
     */
    public function createExam(array $validated, string $creatorId, string $academicSessionId): Exam
    {
        $dto = ExamDTO::fromRequest(new Request($validated), $academicSessionId);

        $school = School::find($validated['school_id']);
        $data = $dto->toArray();
        $data['branch'] = $school?->slug;
        $data = $this->normalizeExamPayload($data);

        return $this->examService->createExam($data, $creatorId);
    }

    /**
     * Update an existing exam and synchronize compositions.
     */
    public function updateExam(Exam $exam, array $validated): void
    {
        DB::transaction(function () use ($exam, $validated) {
            $dto = ExamDTO::fromRequest(new Request($validated), $exam->academic_session_id);

            $school = School::find($validated['school_id']);
            $data = $dto->toArray();
            $data['branch'] = $school?->slug;
            $data = $this->normalizeExamPayload($data, $exam->subject_id);

            $compositions = $data['compositions'];
            unset($data['compositions']);

            $exam->update($data);

            if (! empty($compositions)) {
                $exam->compositions()->delete();
                foreach ($compositions as $compDto) {
                    $exam->compositions()->create($compDto->toArray());
                }
            } else {
                $exam->compositions()->delete();
            }
        });
    }

    public function updateStatus(Exam $exam, string $status): void
    {
        $normalizedStatus = ExamStatus::tryFrom($status);

        if (! $normalizedStatus) {
            throw new InvalidArgumentException('Invalid examination status selected.');
        }

        if ($normalizedStatus === ExamStatus::LIVE && ! $exam->start_time) {
            throw new InvalidArgumentException('You cannot make this examination live until a start date and time has been set.');
        }

        if ($normalizedStatus === ExamStatus::LIVE && ! $exam->questions()->exists()) {
            throw new InvalidArgumentException('You cannot make this examination live until questions have been allocated.');
        }

        $exam->update([
            'status' => $normalizedStatus,
        ]);
    }

    protected function normalizeExamPayload(array $data, ?string $fallbackSubjectId = null): array
    {
        $compositions = array_values(array_filter(
            $data['compositions'] ?? [],
            fn ($composition) => $this->compositionHasSubject($composition)
        ));

        if (! empty($compositions)) {
            $data['subject_id'] = null;
        } else {
            $data['subject_id'] = $data['subject_id'] ?: $fallbackSubjectId;
        }

        $data['compositions'] = array_map(
            fn ($composition) => $this->normalizeComposition($composition, $data['school_class_id'] ?? null),
            $compositions
        );

        return $data;
    }

    protected function compositionHasSubject(mixed $composition): bool
    {
        if ($composition instanceof ExamCompositionDTO) {
            return ! empty($composition->subject_id);
        }

        return is_array($composition) && ! empty($composition['subject_id']);
    }

    protected function normalizeComposition(mixed $composition, ?string $schoolClassId): mixed
    {
        if ($composition instanceof ExamCompositionDTO) {
            if (empty($composition->source_class_id) && ! empty($schoolClassId)) {
                $composition->source_class_id = $schoolClassId;
            }

            return $composition;
        }

        if (is_array($composition) && empty($composition['source_class_id']) && ! empty($schoolClassId)) {
            $composition['source_class_id'] = $schoolClassId;
        }

        return $composition;
    }

    /**
     * Delete an exam if it is safe to do so.
     *
     * @return array{deleted: bool, message: string}
     */
    public function deleteExam(Exam $exam): array
    {
        if ($exam->attempts()->exists()) {
            return [
                'deleted' => false,
                'message' => 'Cannot delete an exam that already has student attempts.',
            ];
        }

        $exam->questions()->detach();
        $exam->delete();

        return [
            'deleted' => true,
            'message' => 'Exam deleted successfully.',
        ];
    }
}
