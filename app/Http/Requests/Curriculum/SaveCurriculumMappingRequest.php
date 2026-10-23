<?php

namespace App\Http\Requests\Curriculum;

use App\Support\AcademicLevels;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCurriculumMappingRequest extends FormRequest
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
            'action' => ['required', Rule::in(['confirm', 'reject', 'save', 'merge'])],
            'classification' => ['sometimes', Rule::in(['topic', 'subtopic', 'section'])],
            'suggested_name' => ['nullable', 'string', 'max:255'],
            'subtopic_id' => ['nullable', 'string', 'exists:subtopics,id'],
            'new_subtopic_name' => ['nullable', 'string', 'max:255'],
            'merge_target_id' => ['required_if:action,merge', 'nullable', 'string', 'exists:curriculum_topic_mappings,id'],
            'subject_id' => ['required_if:action,confirm,save', 'nullable', 'string', 'exists:subjects,id'],
            'class_level' => ['required_if:action,confirm,save', 'nullable', Rule::in(AcademicLevels::classValues())],
            'topic_id' => ['nullable', 'string', 'exists:topics,id'],
            'new_topic_name' => ['nullable', 'string', 'max:255'],
            'review_note' => ['nullable', 'string', 'max:2000'],
            'evidence' => ['required_if:action,confirm,save', 'array', 'max:30'],
            'evidence.*.chunk_id' => ['required', 'string'],
            'evidence.*.excerpt' => ['nullable', 'string', 'min:12', 'max:2000'],
        ];
    }
}
