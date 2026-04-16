<?php

namespace App\Http\Requests\Question;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\SchoolClass;
use App\Models\Subject;
use BackedEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class ImportQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bank:create');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
            'level' => ['nullable', 'string'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'difficulty' => ['required', new Enum(QuestionDifficulty::class)],
            'question_type' => ['nullable', new Enum(QuestionType::class)],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateAcademicContext($validator),
        ];
    }

    protected function validateAcademicContext(Validator $validator): void
    {
        /** @var SchoolClass|null $class */
        $class = SchoolClass::query()
            ->select(['id', 'level', 'school_id'])
            ->find($this->input('school_class_id'));
        /** @var Subject|null $subject */
        $subject = Subject::query()
            ->select(['id', 'level'])
            ->find($this->input('subject_id'));

        if (! $class || ! $subject) {
            return;
        }

        $classLevel = $this->normalizeLevel($class->level);
        $subjectLevel = $this->normalizeLevel($subject->level);
        $selectedLevel = $this->normalizeLevel($this->input('level'));

        if ($selectedLevel && $selectedLevel !== $classLevel) {
            $validator->errors()->add('level', 'Selected level does not match selected class level.');
        }

        if ($classLevel !== $subjectLevel) {
            $validator->errors()->add('subject_id', 'Selected subject level does not match selected class level.');
        }

        $user = $this->user();
        if (
            $user
            && ! $user->can('sys:manage_settings')
            && $user->school_id
            && $class->school_id
            && $class->school_id !== $user->school_id
        ) {
            $validator->errors()->add('school_class_id', 'You cannot import questions into classes outside your branch.');
        }
    }

    protected function normalizeLevel(mixed $level): ?string
    {
        if ($level instanceof BackedEnum) {
            return (string) $level->value;
        }

        if (is_string($level) && $level !== '') {
            return strtolower(trim($level));
        }

        return null;
    }
}
