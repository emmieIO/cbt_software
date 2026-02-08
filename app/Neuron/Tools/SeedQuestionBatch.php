<?php

declare(strict_types=1);

namespace App\Neuron\Tools;

use App\DTOs\QuestionDTO;
use App\Models\User;
use App\Services\QuestionService;
use NeuronAI\Tools\ArrayProperty;
use NeuronAI\Tools\ObjectProperty;
use NeuronAI\Tools\Tool;

class SeedQuestionBatch extends Tool
{
    public function __construct(protected QuestionService $questionService)
    {
        parent::__construct(
            name: 'seed_question_batch',
            description: 'Seed a batch of high-quality assessment questions into the Chrisland Schools Question Bank in a single operation.'
        );

        $this->setMaxTries(20);
    }

    protected function properties(): array
    {
        return [
            new ArrayProperty(
                name: 'questions',
                description: 'The list of questions to be seeded.',
                required: false,
                items: new ObjectProperty(
                    name: 'question',
                    description: 'Question details',
                    class: QuestionDTO::class
                )
            ),
        ];
    }

    /**
     * @param  QuestionDTO[]|null  $questions
     */
    public function __invoke(?array $questions = null): string
    {
        try {
            $inputs = $this->getInputs();
            
            \Illuminate\Support\Facades\Log::debug('AI Tool Call: seed_question_batch', [
                'has_questions_param' => !is_null($questions),
                'questions_count' => $questions ? count($questions) : 0,
                'raw_inputs' => $inputs
            ]);

            if (!$questions) {
                return 'Error: No questions were provided. Please provide an array of questions in the "questions" parameter.';
            }

            // Find a staff user to associate with the creation
            $staff = \App\Models\User::role('staff')->first();

            if (! $staff) {
                return 'Error: No staff user found to associate with the question creation.';
            }

            $this->questionService->createQuestionsBatch($questions, $staff->id);

            $count = count($questions);
            return "Successfully seeded a batch of {$count} questions into the bank.";
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Batch Seeding Error:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return "Error seeding question batch: {$e->getMessage()}";
        }
    }
}
