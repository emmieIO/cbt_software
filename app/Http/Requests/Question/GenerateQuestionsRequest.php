<?php

namespace App\Http\Requests\Question;

use App\Enums\QuestionType;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Topic;
use App\Enums\QuestionDifficulty;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateQuestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'exists:subjects,id'],
            'topic_id' => ['required', 'exists:topics,id'],
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'count' => ['required', 'integer', 'min:1', 'max:20'],
            'difficulty' => ['required', Rule::enum(QuestionDifficulty::class)],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $subject = Subject::query()->find($this->string('subject_id')->toString());
                $topic = Topic::query()->find($this->string('topic_id')->toString());
                $schoolClass = SchoolClass::query()->find($this->string('school_class_id')->toString());

                if (! $subject || ! $topic || ! $schoolClass) {
                    return;
                }

                $subjectLevel = is_string($subject->level) ? $subject->level : $subject->level?->value;
                $classLevel = is_string($schoolClass->level) ? $schoolClass->level : $schoolClass->level?->value;

                if ($subjectLevel !== $classLevel) {
                    $validator->errors()->add('subject_id', 'Selected subject does not match the chosen class level.');
                }

                if ($topic->subject_id !== $subject->id || $topic->school_class_id !== $schoolClass->id) {
                    $validator->errors()->add('topic_id', 'Selected topic does not belong to the chosen subject and class.');
                }

                $user = $this->user();
                $canCreateCrossLevel = $user
                    && ($user->can('sys:manage_settings')
                        || $user->can('access:cross-level-authoring')
                        || $user->can('bank:create_cross_level')
                        || $user->can('exam:create_cross_level'));

                if (! $canCreateCrossLevel && $user?->school) {
                    $schoolLevel = is_string($user->school->type) ? $user->school->type : $user->school->type?->value;

                    if ($schoolLevel && ($subjectLevel !== $schoolLevel || $classLevel !== $schoolLevel)) {
                        $validator->errors()->add('school_class_id', 'You can only generate AI questions within your assigned school level.');
                    }
                }
            },
        ];
    }
}
