<?php

use App\Services\ExtractedDocumentText;
use App\Services\KnowledgeTextNormalizer;

test('it normalizes extracted text while preserving mathematical notation and paragraphs', function () {
    $document = new ExtractedDocumentText(
        text: '',
        pages: [
            [
                'number' => 1,
                'text' => "SS2 MATHEMATICS\n\nQuadratic&#x20;Equations\nUse   x² + 5x + 6 = 0 and \\frac{-b ± √(b²-4ac)}{2a}.\n\nWorked Example\nFactorise \\“x² + 3x + 2\\”.\nPage 1 of 3",
            ],
            [
                'number' => 2,
                'text' => "SS2 MATHEMATICS\n\nFACTORISATION\nThe expression splits into two factors.\n\nKeep this paragraph separate.\nPage 2 of 3",
            ],
            [
                'number' => 3,
                'text' => "SS2 MATHEMATICS\n\nCOMPLETING THE SQUARE\nThe symbols ≤, ≥ and ∑ remain meaningful.\nPage 3 of 3",
            ],
        ],
        method: 'pdf_text',
    );

    $result = (new KnowledgeTextNormalizer)->normalize($document);

    expect($result->pages)->toHaveCount(3)
        ->and($result->text)->not->toContain('&#x20;')
        ->not->toContain('SS2 MATHEMATICS')
        ->not->toContain('Page 1 of 3')
        ->toContain('Quadratic Equations')
        ->toContain('x² + 5x + 6 = 0')
        ->toContain('\\frac{-b ± √(b²-4ac)}{2a}')
        ->toContain('“x² + 3x + 2”')
        ->toContain('≤, ≥ and ∑')
        ->and($result->pages[1]['text'])->toContain("The expression splits into two factors.\n\nKeep this paragraph separate.");
});

test('it removes only reliably repeated edge lines', function () {
    $document = new ExtractedDocumentText(
        text: '',
        pages: [
            ['number' => 1, 'text' => "Shared Header\nUnique first lesson\nShared Footer"],
            ['number' => 2, 'text' => "Shared Header\nUnique second lesson\nShared Footer"],
            ['number' => 3, 'text' => "Different Heading\nUnique third lesson\nDifferent Footer"],
        ],
        method: 'ocr',
    );

    $result = (new KnowledgeTextNormalizer)->normalize($document);

    expect($result->pages[0]['text'])->toBe('Unique first lesson')
        ->and($result->pages[1]['text'])->toBe('Unique second lesson')
        ->and($result->pages[2]['text'])->toContain('Different Heading')
        ->toContain('Different Footer');
});
