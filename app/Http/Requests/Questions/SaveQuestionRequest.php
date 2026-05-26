<?php

namespace App\Http\Requests\Questions;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveQuestionRequest extends FormRequest
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
            'type' => ['required', 'in:multiple_choice,theory'],
            'topic_id' => ['required', 'exists:topics,id'],
            'content' => ['required', 'string'],
            'level' => ['required', 'in:lp,hp,js,ss'],
            'explanation' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');
            $options = $this->decodedArrayInput('options');
            $markingScheme = $this->decodedArrayInput('marking_scheme');

            if ($type === 'multiple_choice') {
                if (count($options) !== 4) {
                    $validator->errors()->add('options', 'Multiple choice questions must have exactly 4 options.');

                    return;
                }

                $correctCount = 0;

                foreach ($options as $index => $option) {
                    if (! is_array($option) || trim((string) ($option['content'] ?? '')) === '') {
                        $validator->errors()->add("options.$index.content", 'Each option must include content.');
                    }

                    if (($option['is_correct'] ?? false) === true) {
                        $correctCount++;
                    }
                }

                if ($correctCount !== 1) {
                    $validator->errors()->add('options', 'Multiple choice questions must have exactly one correct answer.');
                }
            }

            if ($type === 'theory') {
                if ($markingScheme === []) {
                    $validator->errors()->add('marking_scheme', 'Theory questions must include at least one marking point.');

                    return;
                }

                foreach ($markingScheme as $index => $item) {
                    if (! is_array($item) || trim((string) ($item['point'] ?? '')) === '') {
                        $validator->errors()->add("marking_scheme.$index.point", 'Each marking point must include a description.');
                    }

                    $weight = $item['weight'] ?? null;
                    if (! is_numeric($weight) || (int) $weight < 1) {
                        $validator->errors()->add("marking_scheme.$index.weight", 'Each marking point weight must be at least 1.');
                    }
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            ...$validated,
            'image' => $this->file('image'),
            'remove_image' => $this->boolean('remove_image'),
            'options' => $this->decodedArrayInput('options'),
            'marking_scheme' => $this->decodedArrayInput('marking_scheme'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodedArrayInput(string $key): array
    {
        $value = $this->input($key);

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }
}
