<?php

namespace App\Http\Requests\Question;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use BackedEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class BatchStoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bank:create');
    }

    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1', 'max:200'],
            'questions.*.subject_id' => ['required', 'exists:subjects,id'],
            'questions.*.topic_id' => ['required', 'exists:topics,id'],
            'questions.*.school_class_id' => ['required', 'exists:school_classes,id'],
            'questions.*.content' => ['required', 'string', 'min:10'],
            'questions.*.explanation' => ['nullable', 'string'],
            'questions.*.type' => ['required', new Enum(QuestionType::class)],
            'questions.*.difficulty' => ['required', new Enum(QuestionDifficulty::class)],
            'questions.*.image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.options.*.content' => ['required', 'string'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'questions.max' => 'Spreadsheet mode supports up to 200 rows per submission.',
            'questions.*.subject_id.required' => 'The subject is required for all rows.',
            'questions.*.topic_id.required' => 'The topic is required for all rows.',
            'questions.*.school_class_id.required' => 'The class is required for all rows.',
            'questions.*.content.required' => 'The question text is required for all rows.',
            'questions.*.content.min' => 'Each question should be at least 10 characters.',
            'questions.*.options.min' => 'Each question must have at least 2 options.',
            'questions.*.options.max' => 'Each question can have at most 6 options.',
        ];
    }

    /**
     * Custom validation logic for correctness.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $rows = $this->input('questions', []);
            if (! is_array($rows) || empty($rows)) {
                return;
            }

            $topicIds = collect($rows)->pluck('topic_id')->filter()->unique()->values();
            $subjectIds = collect($rows)->pluck('subject_id')->filter()->unique()->values();
            $classIds = collect($rows)->pluck('school_class_id')->filter()->unique()->values();

            $topics = Topic::query()
                ->whereIn('id', $topicIds)
                ->get(['id', 'subject_id', 'school_class_id'])
                ->keyBy('id');
            $subjects = Subject::query()
                ->whereIn('id', $subjectIds)
                ->get(['id', 'level'])
                ->keyBy('id');
            $classes = SchoolClass::query()
                ->whereIn('id', $classIds)
                ->get(['id', 'level', 'school_id'])
                ->keyBy('id');

            $user = $this->user();

            foreach ($rows as $index => $question) {
                $options = collect($question['options'] ?? []);
                $correctCount = $options->where('is_correct', true)->count();
                if ($correctCount !== 1) {
                    $validator->errors()->add("questions.$index.options", 'Exactly one option must be marked as correct.');
                }

                $topicId = $question['topic_id'] ?? null;
                $subjectId = $question['subject_id'] ?? null;
                $classId = $question['school_class_id'] ?? null;

                $topic = $topicId ? $topics->get($topicId) : null;
                $subject = $subjectId ? $subjects->get($subjectId) : null;
                $class = $classId ? $classes->get($classId) : null;

                if ($topic && $subjectId && $topic->subject_id !== $subjectId) {
                    $validator->errors()->add("questions.$index.topic_id", 'Selected topic does not belong to the selected subject.');
                }

                if ($topic && $classId && $topic->school_class_id && $topic->school_class_id !== $classId) {
                    $validator->errors()->add("questions.$index.topic_id", 'Selected topic does not belong to the selected class.');
                }

                $subjectLevel = $subject ? ($subject->level instanceof BackedEnum ? $subject->level->value : $subject->level) : null;
                $classLevel = $class ? ($class->level instanceof BackedEnum ? $class->level->value : $class->level) : null;
                if ($subject && $class && $subjectLevel !== $classLevel) {
                    $validator->errors()->add("questions.$index.school_class_id", 'Class level does not match selected subject level.');
                }

                if (
                    $user
                    && ! $user->can('sys:manage_settings')
                    && $class
                    && $user->school_id
                    && $class->school_id
                    && $class->school_id !== $user->school_id
                ) {
                    $validator->errors()->add("questions.$index.school_class_id", 'You cannot add questions to classes outside your branch.');
                }
            }
        });
    }
}
