<?php

namespace App\Http\Requests\QuestionGeneration;

use App\Models\QuestionGenerationRun;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportGeneratedQuestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $run = $this->route('question_generation_run');

        return $run instanceof QuestionGenerationRun && ($this->user()?->canGenerateQuestions() ?? false)
            && ($this->user()?->canCreateQuestions() ?? false) && $run->requested_by === $this->user()?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['draft_ids' => ['required', 'array', 'min:1', 'max:30'], 'draft_ids.*' => ['required', 'string', 'distinct']];
    }
}
