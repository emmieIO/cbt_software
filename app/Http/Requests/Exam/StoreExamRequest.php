<?php

namespace App\Http\Requests\Exam;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use BackedEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('exam:create');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'school_id' => ['required', 'exists:schools,id'],
            'subject_id' => [
                'required_without:compositions',
                'nullable',
                'exists:subjects,id',
            ],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'duration' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string'],
            'start_time' => ['nullable', 'date', 'after_or_equal:now'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'compositions' => ['nullable', 'array'],
            'compositions.*.subject_id' => ['required', 'exists:subjects,id'],
            'compositions.*.topic_id' => ['nullable', 'exists:topics,id'],
            'compositions.*.question_count' => ['required', 'integer', 'min:1'],
            'compositions.*.marks_per_question' => ['required', 'numeric', 'min:0.1'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateAcademicConsistency($validator),
        ];
    }

    protected function validateAcademicConsistency(Validator $validator): void
    {
        $schoolClassId = $this->input('school_class_id');
        $subjectId = $this->input('subject_id');
        $compositions = $this->input('compositions', []);

        /** @var SchoolClass|null $schoolClass */
        $schoolClass = is_string($schoolClassId) ? SchoolClass::query()->find($schoolClassId) : null;

        if ($schoolClass && is_string($subjectId) && $subjectId !== '') {
            /** @var Subject|null $subject */
            $subject = Subject::query()->find($subjectId);
            $subjectLevel = $this->normalizeLevel($subject?->level);
            $classLevel = $this->normalizeLevel($schoolClass->level);
            if ($subject && $subjectLevel !== $classLevel) {
                $validator->errors()->add('subject_id', 'The selected subject does not match the academic level of the selected class.');
            }
        }

        if (! is_array($compositions) || ! $schoolClass) {
            return;
        }

        foreach ($compositions as $index => $composition) {
            if (! is_array($composition)) {
                continue;
            }

            $compositionSubjectId = $composition['subject_id'] ?? null;
            $compositionTopicId = $composition['topic_id'] ?? null;

            /** @var Subject|null $compositionSubject */
            $compositionSubject = is_string($compositionSubjectId) ? Subject::query()->find($compositionSubjectId) : null;
            $compositionSubjectLevel = $this->normalizeLevel($compositionSubject?->level);
            $classLevel = $this->normalizeLevel($schoolClass->level);
            if ($compositionSubject && $compositionSubjectLevel !== $classLevel) {
                $validator->errors()->add("compositions.$index.subject_id", 'The subject does not match the academic level of the selected class.');
            }

            /** @var Topic|null $topic */
            $topic = is_string($compositionTopicId) && $compositionTopicId !== ''
                ? Topic::query()->find($compositionTopicId)
                : null;

            if ($topic && $topic->subject_id !== $compositionSubjectId) {
                $validator->errors()->add("compositions.$index.topic_id", 'The selected topic does not belong to the chosen subject.');
            }

            if ($topic && $topic->school_class_id !== $schoolClass->id) {
                $validator->errors()->add("compositions.$index.topic_id", 'The selected topic does not belong to the chosen class.');
            }
        }
    }

    protected function normalizeLevel(mixed $level): ?string
    {
        if ($level instanceof BackedEnum) {
            return (string) $level->value;
        }

        return is_string($level) ? $level : null;
    }
}
