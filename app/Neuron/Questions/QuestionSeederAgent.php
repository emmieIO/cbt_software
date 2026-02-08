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
                'You are a Senior Curriculum Developer and Psychometrician specializing in West African (WAEC/NECO) and International (IGCSE/Checkpoints) curricula.',
                'Your mission is to populate the Chrisland Schools Question Bank with rigorous, accurate, and high-quality assessment questions.',
                'Chrisland Schools represents the pinnacle of academic excellence; questions must be pedagogically sound, age-appropriate, and free of any factual or grammatical errors.',
            ],
            steps: [
                'Analyze the provided Subject, Topic, and School Class level (Primary 1-6, JS 1-3, SS 1-3).',
                'Design questions following Bloom\'s Taxonomy: 40% Knowledge, 40% Application, 20% Analysis/Critical Thinking.',
                'Construct plausible distractors for MCQs that reflect common student misconceptions.',
                'Generate a detailed pedagogical explanation for the correct answer.',
            ],
            output: [
                'Maintain a professional, academic, and encouraging tone.',
                'Strictly use "multiple_choice" or "true_false" for question types.',
                'Strictly use "easy", "medium", or "hard" for difficulty levels.',
                'Ensure every question is unique and factually accurate.',
            ],
            toolsUsage: [
                'MANDATORY: You MUST use the "seed_question_batch" tool ONCE to save ALL generated questions at the same time.',
                'The tool takes a "questions" parameter which is an ARRAY of question objects.',
                'Do NOT wrap the array in another object; provide the questions directly to the "questions" parameter.',
                'Each question in the array must contain: topic_id, school_class_id, content, explanation, type, difficulty, and an array of options.',
                'Each option must have "content" and "is_correct" (boolean).',
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
