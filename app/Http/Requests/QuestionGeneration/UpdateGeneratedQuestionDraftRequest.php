<?php

namespace App\Http\Requests\QuestionGeneration;

use App\Models\GeneratedQuestionDraft;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateGeneratedQuestionDraftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $draft = $this->route('generated_question_draft');

        return $draft instanceof GeneratedQuestionDraft
            && ($this->user()?->canGenerateQuestions() ?? false)
            && $draft->run()->where('requested_by', $this->user()?->id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'revision' => ['sometimes', 'integer', 'min:1'],
            'type' => ['required', Rule::in(['multiple_choice', 'short_answer', 'theory'])],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'content' => ['required', 'string', 'max:20000'],
            'explanation' => ['nullable', 'string', 'max:20000'],
            'expected_answer' => ['nullable', 'string', 'max:20000'],
            'options' => ['nullable', 'array'],
            'options.*.content' => ['required_with:options', 'string', 'max:5000'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
            'marking_scheme' => ['nullable', 'array'],
            'marking_scheme.*.point' => ['required_with:marking_scheme', 'string', 'max:5000'],
            'marking_scheme.*.weight' => ['required_with:marking_scheme', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $type = $this->input('type');
            $options = is_array($this->input('options')) ? $this->input('options') : [];
            $scheme = is_array($this->input('marking_scheme')) ? $this->input('marking_scheme') : [];

            if ($type === 'multiple_choice') {
                if (count($options) !== 4) {
                    $validator->errors()->add('options', 'Multiple-choice questions must have exactly four options.');
                }
                if (collect($options)->filter(fn (array $option): bool => (bool) ($option['is_correct'] ?? false))->count() !== 1) {
                    $validator->errors()->add('options', 'Select exactly one correct option.');
                }
            }

            if (in_array($type, ['short_answer', 'theory'], true)) {
                if (trim((string) $this->input('expected_answer')) === '') {
                    $validator->errors()->add('expected_answer', 'An expected answer is required for written questions.');
                }
                if ($scheme === []) {
                    $validator->errors()->add('marking_scheme', 'A marking scheme is required for written questions.');
                }
            }
        }];
    }
}
