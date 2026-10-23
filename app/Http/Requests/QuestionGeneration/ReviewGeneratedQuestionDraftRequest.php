<?php

namespace App\Http\Requests\QuestionGeneration;

use App\Models\GeneratedQuestionDraft;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewGeneratedQuestionDraftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $draft = $this->route('generated_question_draft');

        return $draft instanceof GeneratedQuestionDraft && ($this->user()?->canGenerateQuestions() ?? false)
            && $draft->run()->where('requested_by', $this->user()?->id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['action' => ['required', Rule::in(['approve', 'reject'])], 'revision' => ['required', 'integer', 'min:1'],
            'answers_verified' => ['required_if:action,approve', 'boolean', 'accepted_if:action,approve']];
    }
}
