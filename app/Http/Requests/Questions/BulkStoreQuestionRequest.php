<?php

namespace App\Http\Requests\Questions;

use App\Models\Topic;
use App\Support\AcademicLevels;
use App\Support\RichContent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkStoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1', 'max:100'],
            'questions.*.type' => ['required', 'in:multiple_choice,short_answer,theory'],
            'questions.*.topic_id' => ['required', 'exists:topics,id'],
            'questions.*.content' => ['required', 'string'],
            'questions.*.level' => ['required', 'in:lp,hp,js,ss'],
            'questions.*.class_level' => ['required', Rule::in(AcademicLevels::classValues())],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.content' => ['required_with:questions.*.options', 'string'],
            'questions.*.options.*.is_correct' => ['nullable', 'boolean'],
            'questions.*.marking_scheme' => ['nullable', 'array'],
            'questions.*.marking_scheme.*.point' => ['required_with:questions.*.marking_scheme', 'string'],
            'questions.*.marking_scheme.*.weight' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('questions', []) as $index => $question) {
                $type = $question['type'] ?? null;
                $options = is_array($question['options'] ?? null) ? $question['options'] : [];
                $markingScheme = is_array($question['marking_scheme'] ?? null) ? $question['marking_scheme'] : [];

                if (RichContent::text($question['content'] ?? '') === '') {
                    $validator->errors()->add("questions.$index.content", 'Question content is required.');
                }

                if (! AcademicLevels::classBelongsToLevel((string) ($question['class_level'] ?? ''), (string) ($question['level'] ?? ''))) {
                    $validator->errors()->add("questions.$index.class_level", 'Select a class level that belongs to the selected school level.');
                }

                $topic = Topic::query()->with('subject')->find($question['topic_id'] ?? null);
                if ($topic) {
                    if ($topic->subject?->level && $topic->subject->level !== ($question['level'] ?? null)) {
                        $validator->errors()->add("questions.$index.topic_id", 'Select a topic that belongs to the selected school level.');
                    }

                    if ($topic->class_level && $topic->class_level !== ($question['class_level'] ?? null)) {
                        $validator->errors()->add("questions.$index.topic_id", 'Select a topic that belongs to the selected class level.');
                    }
                }

                if ($type === 'multiple_choice') {
                    if (count($options) !== 4) {
                        $validator->errors()->add("questions.$index.options", 'Multiple choice questions must have exactly 4 options.');
                    }

                    $correctCount = count(array_filter($options, fn ($option) => ($option['is_correct'] ?? false) === true));
                    if ($correctCount !== 1) {
                        $validator->errors()->add("questions.$index.options", 'Multiple choice questions must have exactly one correct answer.');
                    }

                    foreach ($options as $optionIndex => $option) {
                        if (RichContent::text($option['content'] ?? '') === '') {
                            $validator->errors()->add("questions.$index.options.$optionIndex.content", 'Each option must include content.');
                        }
                    }
                }

                if (in_array($type, ['short_answer', 'theory'], true)) {
                    if ($markingScheme === []) {
                        $validator->errors()->add("questions.$index.marking_scheme", 'Written questions must include at least one marking point.');
                    }

                    foreach ($markingScheme as $pointIndex => $item) {
                        if (RichContent::text($item['point'] ?? '') === '') {
                            $validator->errors()->add("questions.$index.marking_scheme.$pointIndex.point", 'Each marking point must include a description.');
                        }
                    }
                }
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function questions(): array
    {
        /** @var array{questions: array<int, array<string, mixed>>} $validated */
        $validated = $this->validated();

        return array_map(function (array $question) {
            $question['content'] = RichContent::sanitize($question['content'] ?? '');
            $question['options'] = array_map(fn (array $option) => [
                ...$option,
                'content' => RichContent::sanitize($option['content'] ?? ''),
            ], $question['options'] ?? []);
            $question['marking_scheme'] = array_map(fn (array $item) => [
                ...$item,
                'point' => RichContent::sanitize($item['point'] ?? ''),
            ], $question['marking_scheme'] ?? []);

            return $question;
        }, $validated['questions']);
    }
}
