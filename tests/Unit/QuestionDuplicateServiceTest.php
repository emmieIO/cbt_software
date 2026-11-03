<?php

use App\Services\QuestionDuplicateService;

test('duplicate matching ignores presentation numbering whitespace and option order', function () {
    $service = new QuestionDuplicateService;
    $options = [['content' => 'Product of factors'], ['content' => 'A sum']];
    expect($service->matches(
        ['content' => '<p>Question 12: Define <strong>factorisation</strong>?</p>', 'options' => $options],
        ['content' => "DEFINE   factorisation.\n", 'options' => array_reverse($options)],
    ))->toBeTrue();
});

test('duplicate matching detects near text but preserves meaningful maths and negation', function () {
    $service = new QuestionDuplicateService;
    expect($service->matches(['content' => 'Explain the process of solving a quadratic equation using factorisation.'],
        ['content' => 'Explain the process of solving quadratic equations using factorisation.']))->toBeTrue();

    foreach ([
        ['Solve x² - 5x + 6 = 0.', 'Solve x² - 7x + 6 = 0.'],
        ['Solve x + 2 = 4.', 'Solve x - 2 = 4.'],
        ['Which statement is correct?', 'Which statement is not correct?'],
        ['Compare x < 2.', 'Compare x > 2.'],
        ['Find 1.25 times x.', 'Find 1.75 times x.'],
    ] as [$left, $right]) {
        expect($service->matches(['content' => $left], ['content' => $right]))->toBeFalse();
    }
});

test('generic multiple choice stems with different options are distinct', function () {
    $service = new QuestionDuplicateService;
    expect($service->matches(
        ['content' => 'Which of these is correct?', 'options' => [['content' => 'A plant has roots.']]],
        ['content' => 'Which of these is correct?', 'options' => [['content' => 'The Earth orbits the Sun.']]],
    ))->toBeFalse();
    expect($service->matches(
        ['content' => 'What is 2 + 2?', 'options' => [['content' => '4'], ['content' => '5']]],
        ['content' => 'What is 2 + 2?', 'options' => [['content' => '4'], ['content' => '6']]],
    ))->toBeTrue();
});

test('rich math and plain latex compare equally without stripping operators', function () {
    $service = new QuestionDuplicateService;
    expect($service->matches(
        ['content' => '<p>Solve <span data-type="inline-math" data-latex="x^2 + 2x = 0"></span>.</p>'],
        ['content' => 'Solve \\(x^2 + 2x = 0\\).'],
    ))->toBeTrue();
    expect($service->normalize('Find x<sup>2</sup>.'))->toContain('x^{2}');
});
