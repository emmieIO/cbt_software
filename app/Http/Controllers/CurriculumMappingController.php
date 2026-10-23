<?php

namespace App\Http\Controllers;

use App\Http\Requests\Curriculum\ConfirmCurriculumStructureRequest;
use App\Http\Requests\Curriculum\SaveCurriculumMappingRequest;
use App\Models\CurriculumTopicMapping;
use App\Models\KnowledgeDocument;
use App\Services\CurriculumMappingService;
use App\Services\CurriculumStructureService;
use App\Services\KnowledgeDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurriculumMappingController extends Controller
{
    public function confirmStructure(ConfirmCurriculumStructureRequest $request, KnowledgeDocument $knowledgeDocument, CurriculumStructureService $service): RedirectResponse
    {
        $service->confirm($knowledgeDocument, $request->validated(), $request->user());

        return back()->with('success', 'Curriculum structure confirmed. The topic and subtopics are now available in the question generator.');
    }

    public function identify(Request $request, KnowledgeDocument $knowledgeDocument): RedirectResponse
    {
        abort_unless($request->user()?->canReviewCurriculum(), 403);
        $document = DB::transaction(function () use ($knowledgeDocument): KnowledgeDocument {
            $document = KnowledgeDocument::query()->lockForUpdate()->findOrFail($knowledgeDocument->id);
            abort_unless($document->approval_status === KnowledgeDocument::APPROVAL_APPROVED
                && $document->processing_status === KnowledgeDocument::PROCESSING_COMPLETED, 409);
            abort_if(in_array($document->topic_detection_status, ['queued', 'processing'], true), 409);
            if ($document->topic_detection_status === 'completed') {
                $document->topic_detection_cursor = 0;
            }
            $document->topic_detection_status = 'queued';
            $document->topic_detection_error = null;
            $document->save();

            return $document;
        });

        if (! app(KnowledgeDocumentService::class)->queueTopicDetection($document)) {
            return back()->with('error', 'Topic identification could not be queued. Please retry when the queue service is available.');
        }

        return back()->with('success', 'Curriculum topic identification queued. Suggestions will need review.');
    }

    public function store(SaveCurriculumMappingRequest $request, KnowledgeDocument $knowledgeDocument, CurriculumMappingService $service): RedirectResponse
    {
        abort_unless($request->input('action') === 'confirm', 422);
        $service->review($knowledgeDocument, null, $request->validated(), $request->user());

        return back()->with('success', 'Curriculum coverage confirmed.');
    }

    public function update(SaveCurriculumMappingRequest $request, KnowledgeDocument $knowledgeDocument, CurriculumTopicMapping $curriculumMapping, CurriculumMappingService $service): RedirectResponse
    {
        abort_unless($curriculumMapping->knowledge_document_id === $knowledgeDocument->id, 404);
        $service->review($knowledgeDocument, $curriculumMapping, $request->validated(), $request->user());

        return back()->with('success', 'Curriculum mapping reviewed.');
    }
}
