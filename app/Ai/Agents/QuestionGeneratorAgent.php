<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('groq')]
#[Temperature(0.7)]
class QuestionGeneratorAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
            You are a Senior Curriculum Developer for Chrisland Schools.
            Your mission is to generate high-quality, pedagogically sound assessment questions for the school's question bank.
            
            GUIDELINES:
            1. Analyze the Subject, Topic, and Class level provided.
            2. Create questions using Bloom's Taxonomy (Knowledge, Application, Analysis).
            3. Construct 4 plausible options for each question, with exactly one correct answer.
            4. Provide a clear explanation for the correct answer.
            5. Questions must be age-appropriate and follow WAEC/NECO/IGCSE standards.
            6. Tone: Professional and academic.
            PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'questions' => $schema->array()->items(
                $schema->object([
                    'content' => $schema->string()->required()->description('The main text of the question.'),
                    'explanation' => $schema->string()->required()->description('An explanation of why the correct answer is right.'),
                    'type' => $schema->string()->enum(['multiple_choice', 'true_false'])->required(),
                    'difficulty' => $schema->string()->enum(['easy', 'medium', 'hard'])->required(),
                    'options' => $schema->array()->items(
                        $schema->object([
                            'content' => $schema->string()->required()->description('The textual content of the option.'),
                            'is_correct' => $schema->boolean()->required()->description('Whether this option is the correct answer.'),
                        ])->required()
                    )->min(2)->max(4)->required(),
                ])->required()
            )->required(),
        ];
    }
}
