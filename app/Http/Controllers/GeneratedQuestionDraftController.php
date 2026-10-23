<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionGeneration\ReviewGeneratedQuestionDraftRequest;
use App\Http\Requests\QuestionGeneration\UpdateGeneratedQuestionDraftRequest;
use App\Models\GeneratedQuestionDraft;
use App\Models\QuestionGenerationRun;
use App\Services\GeneratedQuestionReviewService;
use App\Support\RichContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GeneratedQuestionDraftController extends Controller
{
    public function update(
        UpdateGeneratedQuestionDraftRequest $request,
        GeneratedQuestionDraft $generatedQuestionDraft,
        GeneratedQuestionReviewService $service,
    ): RedirectResponse {
        $data = $request->validated();
        DB::transaction(function () use ($request, $generatedQuestionDraft, $service, $data): void {
            $run = QuestionGenerationRun::query()->lockForUpdate()->findOrFail($generatedQuestionDraft->question_generation_run_id);
            $service->authorize($request->user(), $run);
            $generatedQuestionDraft = $run->drafts()->lockForUpdate()->findOrFail($generatedQuestionDraft->id);
            abort_if($generatedQuestionDraft->review_status === 'imported' || $generatedQuestionDraft->question_id, 409, 'Edit the imported question in the question bank.');
            if (isset($data['revision']) && $data['revision'] !== $generatedQuestionDraft->revision) {
                throw ValidationException::withMessages(['draft' => 'The draft changed. Reload before editing.']);
            }
            $service->audit($generatedQuestionDraft, $request->user(), 'before_edit');
            $type = $data['type'];

            $generatedQuestionDraft->update([
                'type' => $type,
                'difficulty' => $data['difficulty'],
                'content' => $this->clean($data['content']),
                'explanation' => $this->clean($data['explanation'] ?? null) ?: null,
                'expected_answer' => $type === 'multiple_choice'
                    ? null
                    : ($this->clean($data['expected_answer'] ?? null) ?: null),
                'options' => $type === 'multiple_choice'
                    ? collect($data['options'])->map(fn (array $option): array => [
                        'content' => $this->clean($option['content']),
                        'is_correct' => (bool) $option['is_correct'],
                    ])->values()->all()
                    : null,
                'marking_scheme' => $type !== 'multiple_choice'
                    ? collect($data['marking_scheme'])->map(fn (array $item): array => [
                        'point' => $this->clean($item['point']),
                        'weight' => (int) $item['weight'],
                    ])->values()->all()
                    : null,
                'validation_errors' => null,
                'is_valid' => true,
                'edited_at' => now(),
                'edited_by' => $request->user()->id, 'revision' => $generatedQuestionDraft->revision + 1,
                'review_status' => 'draft', 'approved_hash' => null, 'answers_verified' => false, 'reviewed_by' => null, 'reviewed_at' => null, 'import_errors' => null,
            ]);
            try {
                $service->validateDraft($generatedQuestionDraft, false);
            } catch (ValidationException $exception) {
                $generatedQuestionDraft->update(['is_valid' => false, 'validation_errors' => $exception->validator->errors()->all()]);
            }
            $service->audit($generatedQuestionDraft, $request->user(), 'edit');
        });

        return back()->with('success', 'Draft changes saved.');
    }

    public function review(ReviewGeneratedQuestionDraftRequest $request, GeneratedQuestionDraft $generatedQuestionDraft, GeneratedQuestionReviewService $service): RedirectResponse
    {
        $service->review($generatedQuestionDraft, $request->user(), $request->validated());

        return back()->with('success', $request->input('action') === 'approve' ? 'Draft approved.' : 'Draft rejected.');
    }

    private function clean(?string $content): string
    {
        return RichContent::sanitize($content);
    }
}
