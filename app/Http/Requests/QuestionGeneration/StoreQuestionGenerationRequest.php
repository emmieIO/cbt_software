<?php

namespace App\Http\Requests\QuestionGeneration;

use App\Models\Subject;
use App\Models\Subtopic;
use App\Models\Topic;
use App\Services\CurriculumMappingService;
use App\Support\AcademicLevels;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuestionGenerationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canGenerateQuestions() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'string', 'exists:subjects,id'],
            'topic_id' => ['required', 'string', 'exists:topics,id'],
            'subtopic_id' => ['nullable', 'string', 'exists:subtopics,id'],
            'level' => ['required', Rule::in(['lp', 'hp', 'js', 'ss'])],
            'class_level' => ['required', Rule::in(AcademicLevels::classValues())],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('knowledge.generation.max_questions', 30)],
            'type_distribution' => ['required', 'array:multiple_choice,short_answer,theory'],
            'type_distribution.multiple_choice' => ['required', 'integer', 'min:0'],
            'type_distribution.short_answer' => ['required', 'integer', 'min:0'],
            'type_distribution.theory' => ['required', 'integer', 'min:0'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard', 'mixed'])],
            'additional_instructions' => [
                'nullable',
                'string',
                'max:'.config('knowledge.generation.max_instruction_length', 1000),
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $distribution = $this->input('type_distribution', []);
            $total = collect(is_array($distribution) ? $distribution : [])->sum(fn (mixed $count): int => (int) $count);
            if ($total !== (int) $this->input('quantity')) {
                $validator->errors()->add('type_distribution', 'The question type counts must add up to the requested quantity.');
            }

            if (! AcademicLevels::classBelongsToLevel($this->string('class_level')->toString(), $this->string('level')->toString())) {
                $validator->errors()->add('class_level', 'The selected class does not belong to the selected level.');
            }

            $subject = Subject::query()->find($this->input('subject_id'));
            if ($subject && $subject->level !== $this->input('level')) {
                $validator->errors()->add('subject_id', 'The selected subject does not belong to the selected level.');
            }

            $topic = Topic::query()->find($this->input('topic_id'));
            $subtopic = filled($this->input('subtopic_id')) ? Subtopic::query()->find($this->input('subtopic_id')) : null;
            if ($subtopic && $subtopic->topic_id !== $topic?->id) {
                $validator->errors()->add('subtopic_id', 'The subtopic must belong to the selected topic.');
            }
            if ($topic && ($topic->subject_id !== $this->input('subject_id') || $topic->class_level !== $this->input('class_level'))) {
                $validator->errors()->add('topic_id', 'The selected topic does not belong to this subject and class.');
            }
            if (! $validator->errors()->isNotEmpty() && app(CurriculumMappingService::class)->chunkIds(
                $this->string('subject_id')->toString(), $this->string('class_level')->toString(), $this->string('topic_id')->toString(),
                $subtopic?->id,
            ) === []) {
                $validator->errors()->add('topic_id', 'No confirmed curriculum coverage is available for this subject, class and topic.');
            }
        }];
    }
}
