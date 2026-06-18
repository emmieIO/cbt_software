<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

class QuestionParserAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function provider(): Lab
    {
        return Lab::DeepSeek;
    }

    public function model(): string
    {
        return config('ai.question_parser_model', 'deepseek-chat');
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INST'
You are an expert educational content parser. Your task is to extract structured examination questions from raw text extracted from a PDF.
Analyze the text and identify questions, their options (if multiple choice), the correct answers, and any marking schemes.
The PDF may be written as a teacher-prepared question document rather than a spreadsheet. Treat headings such as "Question", "Options", "Correct answer", "Expected answer", "Marking scheme", "Subject", "Topic", "Level", and "Class" as strong extraction hints.
Marking schemes may be written as bullet lists or numbered lists. Convert each marking point into a structured point with an integer weight.
Convert mathematical formulas and expressions into KaTeX-compatible LaTeX before returning the structured data. Use inline math delimiters for formulas inside prose, for example \(F = ma\), \(4(a + 3)\), \(x^2 + y^2 = z^2\), \(\frac{a}{b}\), \(V = IR\). Use display math delimiters for standalone equations, for example \[F = ma\].
Do not duplicate formulas. If PDF extraction repeats a formula as "F=maF=ma" or "F = ma F = ma", return it once as \(F = ma\).
Apply this math formatting consistently in question content, options, correct answers, explanations, and marking scheme points.

Identify the Subject and Topic if mentioned, or infer them from context if possible.
Ensure the output matches the provided JSON schema exactly.
For Multiple Choice Questions (MCQ):
- Provide 4 options (A, B, C, D).
- Identify the correct answer as 'A', 'B', 'C', or 'D'.
For Short Answer or Theory Questions:
- Identify marking points for the marking scheme.

If 'level' or 'class_level' are not obvious, leave them null.
Valid levels: 'lp' (Lower Primary), 'hp' (Higher Primary), 'js' (Junior Secondary), 'ss' (Senior Secondary).
Valid class levels must be numeric strings from '1' to '12', even when the source PDF writes school names:
- Primary 1, Primary 2, Primary 3 map to '1', '2', '3'
- Primary 4, Primary 5, Primary 6 map to '4', '5', '6'
- JSS 1, JSS 2, JSS 3 map to '7', '8', '9'
- SS 1, SS 2, SS 3 map to '10', '11', '12'
INST;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'questions' => $schema->array()->items(
                $schema->object(fn ($schema) => [
                    'subject_name' => $schema->string()->required(),
                    'topic_name' => $schema->string()->required(),
                    'type' => $schema->string()->enum(['multiple_choice', 'short_answer', 'theory'])->required(),
                    'content' => $schema->string()->required(),
                    'image_url' => $schema->string()->nullable(),
                    'explanation' => $schema->string()->nullable(),
                    'level' => $schema->string()->enum(['lp', 'hp', 'js', 'ss'])->nullable(),
                    'class_level' => $schema->string()->nullable(),
                    'options' => $schema->array()->items($schema->string()),
                    'correct_answer' => $schema->string()->nullable(),
                    'marking_scheme' => $schema->array()->items(
                        $schema->object(fn ($schema) => [
                            'point' => $schema->string()->required(),
                            'weight' => $schema->integer()->min(1)->required(),
                        ])
                    ),
                ])
            )->required(),
        ];
    }
}
