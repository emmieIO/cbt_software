<?php

namespace App\Services\Exam;

use App\DTOs\ExamDTO;
use App\Models\Exam;
use App\Models\School;
use App\Services\ExamService;
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

            $compositions = $data['compositions'];
            unset($data['compositions']);

            if (empty($compositions) && is_null($data['subject_id'])) {
                $data['subject_id'] = $exam->subject_id;
            }

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
