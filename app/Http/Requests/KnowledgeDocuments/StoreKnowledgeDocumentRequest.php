<?php

namespace App\Http\Requests\KnowledgeDocuments;

use App\Models\KnowledgeDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreKnowledgeDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canManageKnowledgeBase() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', Rule::in(KnowledgeDocument::DOCUMENT_TYPES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => [
                'required',
                File::types(['pdf', 'doc', 'docx', 'txt', 'md', 'rtf', 'odt'])->max('25mb'),
            ],
        ];
    }

    /** @return array{title: string, document_type: string, description: string|null} */
    public function payload(): array
    {
        /** @var array{title: string, document_type: string, description?: string|null} $validated */
        $validated = $this->safe()->only(['title', 'document_type', 'description']);

        return [
            ...$validated,
            'description' => $validated['description'] ?? null,
        ];
    }

    public function uploadedFile(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('file');

        return $file;
    }
}
