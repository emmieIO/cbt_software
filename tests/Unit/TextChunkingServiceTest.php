<?php

use App\Services\ExtractedDocumentText;
use App\Services\TextChunkingService;

test('it creates deterministic overlapping chunks within the configured token range', function () {
    $paragraphs = [];

    for ($paragraph = 1; $paragraph <= 24; $paragraph++) {
        $words = [];

        for ($word = 1; $word <= 80; $word++) {
            $words[] = "p{$paragraph}word{$word}";
        }

        $paragraphs[] = implode(' ', $words);
    }

    $document = new ExtractedDocumentText(
        text: implode("\n\n", $paragraphs),
        pages: [
            ['number' => 1, 'text' => implode("\n\n", array_slice($paragraphs, 0, 12))],
            ['number' => 2, 'text' => implode("\n\n", array_slice($paragraphs, 12))],
        ],
        method: 'pdf_text',
    );
    $service = new TextChunkingService;

    $chunks = $service->chunk($document);

    expect($chunks)->toBe($service->chunk($document))
        ->and(count($chunks))->toBeGreaterThan(2);

    foreach (array_slice($chunks, 0, -1) as $chunk) {
        expect($chunk['estimated_token_count'])
            ->toBeGreaterThanOrEqual(TextChunkingService::MIN_TOKENS)
            ->toBeLessThanOrEqual(TextChunkingService::MAX_TOKENS);
    }

    foreach ($chunks as $index => $chunk) {
        expect($chunk['sequence'])->toBe($index + 1)
            ->and($chunk['content_hash'])->toBe(hash('sha256', $chunk['content']))
            ->and($chunk['metadata']['page_start'])->toBeGreaterThanOrEqual(1)
            ->and($chunk['metadata']['page_end'])->toBeLessThanOrEqual(2);
    }

});

test('it keeps a short document as one chunk', function () {
    $document = new ExtractedDocumentText(
        text: 'A short lesson note.',
        pages: [['number' => 4, 'text' => 'A short lesson note.']],
        method: 'ocr',
    );

    $chunks = (new TextChunkingService)->chunk($document);

    expect($chunks)->toHaveCount(1)
        ->and($chunks[0]['metadata'])->toBe(['page_start' => 4, 'page_end' => 4]);
});

test('it prefers headings and keeps a worked example together', function () {
    $introduction = implode(' ', array_fill(0, 360, 'algebraic concept'));
    $workedExample = implode("\n\n", [
        'Worked Example 1',
        'Factorise x² + 5x + 6 by finding two numbers whose product is 6 and whose sum is 5.',
        'Solution: The numbers are 2 and 3, so x² + 5x + 6 = (x + 2)(x + 3).',
    ]);
    $document = new ExtractedDocumentText(
        text: '',
        pages: [[
            'number' => 7,
            'text' => $introduction."\n\nFACTORISATION METHODS\n\n".$workedExample."\n\nCHECKING THE ROOTS\n\nSubstitute each root into the original equation.",
        ]],
        method: 'pdf_text',
    );

    $chunks = (new TextChunkingService)->chunk($document);
    $factorisationChunk = collect($chunks)->first(
        fn (array $chunk): bool => str_contains($chunk['content'], 'FACTORISATION METHODS'),
    );

    expect(count($chunks))->toBeGreaterThan(1)
        ->and($factorisationChunk)->not->toBeNull()
        ->and($factorisationChunk['content'])->toStartWith('FACTORISATION METHODS')
        ->toContain('Worked Example 1')
        ->toContain('Solution: The numbers are 2 and 3')
        ->and($factorisationChunk['metadata'])->toBe(['page_start' => 7, 'page_end' => 7]);
});
