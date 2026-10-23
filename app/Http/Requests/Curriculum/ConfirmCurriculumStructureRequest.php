<?php

namespace App\Http\Requests\Curriculum;

use App\Support\AcademicLevels;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmCurriculumStructureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canReviewCurriculum() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'chunk_version' => ['required', 'integer', 'min:1'],
            'review_token' => ['required', 'string', 'size:64'],
            'subject_id' => ['required', 'string', 'exists:subjects,id'],
            'class_level' => ['required', Rule::in(AcademicLevels::classValues())],
            'topic' => ['required', 'array:name,mapping_ids,evidence'],
            'topic.name' => ['required', 'string', 'max:255'],
            'topic.mapping_ids' => ['present', 'array', 'max:100'],
            'topic.mapping_ids.*' => ['required', 'string', 'distinct'],
            'topic.evidence' => ['required', 'array', 'min:1', 'max:100'],
            'topic.evidence.*.chunk_id' => ['required', 'string'],
            'topic.evidence.*.excerpt' => ['required', 'string', 'min:12', 'max:2000'],
            'children' => ['present', 'array', 'max:100'],
            'children.*' => ['required', 'array:name,classification,mapping_ids,evidence'],
            'children.*.name' => ['required', 'string', 'max:255'],
            'children.*.classification' => ['required', Rule::in(['subtopic', 'section'])],
            'children.*.mapping_ids' => ['present', 'array', 'max:100'],
            'children.*.mapping_ids.*' => ['required', 'string'],
            'children.*.evidence' => ['required', 'array', 'min:1', 'max:100'],
            'children.*.evidence.*.chunk_id' => ['required', 'string'],
            'children.*.evidence.*.excerpt' => ['required', 'string', 'min:12', 'max:2000'],
        ];
    }
}
