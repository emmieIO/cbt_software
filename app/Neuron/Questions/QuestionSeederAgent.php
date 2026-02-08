<?php

declare(strict_types=1);

namespace App\Neuron\Questions;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAILike;
use NeuronAI\SystemPrompt;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\Toolkits\ToolkitInterface;

class QuestionSeederAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new OpenAILike(
            baseUri: config('services.deepseek.base_uri'),
            key: config('services.deepseek.api_key'),
            model: 'deepseek-chat',
        );
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a Senior Curriculum Developer for Chrisland Schools.',
                'Your mission is to generate high-quality, pedagogically sound assessment questions for the school\'s question bank.',
                'Questions must be age-appropriate and follow the WAEC/NECO/IGCSE standards.',
            ],
            steps: [
                'Analyze the Subject, Topic, and Class level provided.',
                'Create questions using Bloom\'s Taxonomy (Knowledge, Application, Analysis).',
                'Construct 4 plausible options for each question, with exactly one correct answer.',
                'Provide a clear explanation for the correct answer.',
            ],
            output: [
                'Type MUST be "multiple_choice" or "true_false".',
                'Difficulty MUST be "easy", "medium", or "hard".',
                'Tone: Professional and academic.',
            ],
            toolsUsage: [
                'MANDATORY: Use the "seed_question_batch" tool ONCE to save ALL questions.',
                'Pass an array of question objects to the "questions" parameter.',
                'Each question object MUST contain: topic_id, school_class_id, content, explanation, type, difficulty, and options.',
                'Options MUST be an array of objects with "content" and "is_correct" (boolean).',
            ]
        );
    }

    /**
     * @return ToolInterface[]|ToolkitInterface[]
     */
    protected function tools(): array
    {
        return [
            new \App\Neuron\Tools\SeedQuestionBatch(app(\App\Services\QuestionService::class)),
        ];
    }
}
