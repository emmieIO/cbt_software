<?php

use App\Models\QuestionGenerationRun;
use App\Services\GeneratedQuestionValidator;

function validatorRun(array $attributes = []): QuestionGenerationRun
{
    return (new QuestionGenerationRun)->forceFill([
        'requested_count' => 1,
        'type_distribution' => ['multiple_choice' => 1, 'short_answer' => 0, 'theory' => 0],
        'difficulty' => 'medium',
        ...$attributes,
    ]);
}

function validatorSources(): array
{
    return [[
        'chunk_id' => '01SOURCE000000000000000001',
        'document_id' => '01DOCUMENT000000000000001',
        'document_title' => 'Mathematics Lesson Note',
        'original_name' => 'mathematics.pdf',
        'page_start' => 2,
        'page_end' => 2,
        'similarity' => 0.91,
        'content' => 'A quadratic equation has degree two.',
    ]];
}

test('it accepts a grounded multiple choice question with four options and one answer', function () {
    $result = (new GeneratedQuestionValidator)->validate(validatorRun(), [[
        'type' => 'multiple_choice',
        'difficulty' => 'medium',
        'content' => 'Which expression is quadratic?',
        'options' => [
            ['content' => '\\(x^2 + 2x + 1\\)', 'is_correct' => true],
            ['content' => '\\(x + 1\\)', 'is_correct' => false],
            ['content' => '\\(3\\)', 'is_correct' => false],
            ['content' => '\\(x^3\\)', 'is_correct' => false],
        ],
        'source_chunk_ids' => ['01SOURCE000000000000000001'],
    ]], validatorSources());

    expect($result['errors'])->toBe([])
        ->and($result['drafts'])->toHaveCount(1)
        ->and($result['drafts'][0]['is_valid'])->toBeTrue()
        ->and($result['drafts'][0]['source_references'][0]['document_title'])->toBe('Mathematics Lesson Note')
        ->and($result['drafts'][0]['warnings'])->toContain('Mathematical content requires teacher verification for notation and correctness.');
});

test('it rejects malformed options fabricated citations and duplicate questions', function () {
    $run = validatorRun([
        'requested_count' => 2,
        'type_distribution' => ['multiple_choice' => 2, 'short_answer' => 0, 'theory' => 0],
    ]);
    $question = [
        'type' => 'multiple_choice',
        'difficulty' => 'medium',
        'content' => 'What is a quadratic equation?',
        'options' => [
            ['content' => 'An equation', 'is_correct' => true],
            ['content' => 'A second answer', 'is_correct' => true],
            ['content' => 'A third answer', 'is_correct' => false],
        ],
        'source_chunk_ids' => ['fabricated-chunk'],
    ];

    $result = (new GeneratedQuestionValidator)->validate($run, [$question, $question], validatorSources());

    expect($result['drafts'][0]['is_valid'])->toBeFalse()
        ->and($result['drafts'][0]['validation_errors'])->toContain('Multiple-choice questions must have exactly four options.')
        ->and($result['drafts'][0]['validation_errors'])->toContain('Multiple-choice questions must have exactly one correct option.')
        ->and($result['drafts'][0]['validation_errors'])->toContain('One or more source citations were not supplied to the generator.')
        ->and($result['drafts'][1]['validation_errors'])->toContain('Question duplicates another generated draft.')
        ->and($result['drafts'][0]['source_references'])->toBe([]);
});

test('it requires expected answers marking schemes and the requested type distribution', function () {
    $run = validatorRun([
        'type_distribution' => ['multiple_choice' => 0, 'short_answer' => 0, 'theory' => 1],
    ]);

    $result = (new GeneratedQuestionValidator)->validate($run, [[
        'type' => 'short_answer',
        'difficulty' => 'easy',
        'content' => 'Define factorisation.',
        'expected_answer' => null,
        'marking_scheme' => [],
        'source_chunk_ids' => ['01SOURCE000000000000000001'],
    ]], validatorSources());

    expect($result['drafts'][0]['validation_errors'])
        ->toContain('Difficulty does not match the generation request.')
        ->toContain('Written questions require an expected answer.')
        ->toContain('Written questions require a marking scheme.')
        ->and($result['errors'])->toContain('Expected 0 short_answer questions, but received 1.')
        ->toContain('Expected 1 theory questions, but received 0.');
});
