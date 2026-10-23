<?php

namespace App\Services;

class TextChunkingService
{
    public const MIN_TOKENS = 700;

    public const TARGET_TOKENS = 850;

    public const MAX_TOKENS = 1000;

    public const OVERLAP_TOKENS = 125;

    /**
     * @return array<int, array{
     *     sequence: int,
     *     content: string,
     *     content_hash: string,
     *     estimated_token_count: int,
     *     metadata: array{page_start: int, page_end: int}
     * }>
     */
    public function chunk(ExtractedDocumentText $document): array
    {
        $units = $this->buildUnits($document->pages);

        if ($units === []) {
            return [];
        }

        $chunks = [];
        $currentUnits = [];
        $currentTokens = 0;

        foreach ($units as $unit) {
            $startsNewSection = $unit['starts_section'] && $currentTokens >= self::MIN_TOKENS;
            $wouldExceedTarget = $currentTokens >= self::MIN_TOKENS
                && $currentTokens + $unit['tokens'] > self::TARGET_TOKENS;
            $wouldExceedMaximum = $currentTokens > 0
                && $currentTokens + $unit['tokens'] > self::MAX_TOKENS;

            if ($currentUnits !== [] && ($startsNewSection || $wouldExceedTarget || $wouldExceedMaximum)) {
                $chunks[] = $this->makeChunk(count($chunks) + 1, $currentUnits);
                $currentUnits = $startsNewSection ? [] : $this->overlapUnits($currentUnits);
                $currentTokens = array_sum(array_column($currentUnits, 'tokens'));

                if ($currentTokens + $unit['tokens'] > self::MAX_TOKENS) {
                    $currentUnits = [];
                    $currentTokens = 0;
                }
            }

            $currentUnits[] = $unit;
            $currentTokens += $unit['tokens'];
        }

        if ($currentUnits !== []) {
            $chunks[] = $this->makeChunk(count($chunks) + 1, $currentUnits);
        }

        return $chunks;
    }

    public function estimateTokenCount(string $text): int
    {
        preg_match_all('/[\p{L}\p{N}]+|[^\s\p{L}\p{N}]/u', $text, $matches);

        return array_sum(array_map(
            fn (string $token): int => preg_match('/^[\p{L}\p{N}]+$/u', $token) === 1
                ? max(1, (int) ceil(mb_strlen($token) / 4))
                : 1,
            $matches[0],
        ));
    }

    /**
     * @param  array<int, array{number: int, text: string}>  $pages
     * @return array<int, array{page: int, text: string, tokens: int, starts_section: bool}>
     */
    private function buildUnits(array $pages): array
    {
        $units = [];

        foreach ($pages as $page) {
            $paragraphs = array_values(array_filter(array_map(
                fn (string $paragraph): string => trim($paragraph),
                preg_split('/\n\s*\n/u', trim($page['text'])) ?: [],
            )));

            for ($index = 0; $index < count($paragraphs); $index++) {
                $paragraph = $paragraphs[$index];

                if ($this->isWorkedExampleStart($paragraph)) {
                    $exampleParagraphs = [$paragraph];

                    while (isset($paragraphs[$index + 1])
                        && ! $this->isHeading($paragraphs[$index + 1])
                        && ! $this->isWorkedExampleStart($paragraphs[$index + 1])) {
                        $exampleParagraphs[] = $paragraphs[++$index];
                    }

                    $this->appendSemanticBlock($units, $page['number'], $exampleParagraphs, true);

                    continue;
                }

                $this->appendSemanticBlock(
                    $units,
                    $page['number'],
                    [$paragraph],
                    $this->isHeading($paragraph),
                );
            }
        }

        return $units;
    }

    /**
     * @param  array<int, array{page: int, text: string, tokens: int, starts_section: bool}>  $units
     * @param  array<int, string>  $paragraphs
     */
    private function appendSemanticBlock(array &$units, int $page, array $paragraphs, bool $startsSection): void
    {
        $text = implode("\n\n", $paragraphs);

        if ($this->estimateTokenCount($text) <= self::MAX_TOKENS) {
            $units[] = $this->unit($page, $text, $startsSection);

            return;
        }

        $isFirst = true;

        foreach ($paragraphs as $paragraph) {
            foreach ($this->splitOversizedParagraph($paragraph) as $part) {
                $units[] = $this->unit($page, $part, $startsSection && $isFirst);
                $isFirst = false;
            }
        }
    }

    /** @return array<int, string> */
    private function splitOversizedParagraph(string $paragraph): array
    {
        if ($this->estimateTokenCount($paragraph) <= self::MAX_TOKENS) {
            return [$paragraph];
        }

        $sentences = preg_split('/(?<=[.!?])\s+(?=[\p{Lu}\p{N}])/u', $paragraph) ?: [$paragraph];
        $parts = [];
        $current = [];
        $tokens = 0;

        foreach ($sentences as $sentence) {
            $sentenceTokens = $this->estimateTokenCount($sentence);

            if ($sentenceTokens > self::MAX_TOKENS) {
                if ($current !== []) {
                    $parts[] = implode(' ', $current);
                    $current = [];
                    $tokens = 0;
                }

                array_push($parts, ...$this->splitWords($sentence));

                continue;
            }

            if ($current !== [] && $tokens + $sentenceTokens > self::MAX_TOKENS) {
                $parts[] = implode(' ', $current);
                $current = [];
                $tokens = 0;
            }

            $current[] = $sentence;
            $tokens += $sentenceTokens;
        }

        if ($current !== []) {
            $parts[] = implode(' ', $current);
        }

        return $parts;
    }

    /** @return array<int, string> */
    private function splitWords(string $text): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $parts = [];
        $current = [];
        $tokens = 0;

        foreach ($words as $word) {
            $wordTokens = $this->estimateTokenCount($word);

            if ($current !== [] && $tokens + $wordTokens > self::MAX_TOKENS) {
                $parts[] = implode(' ', $current);
                $current = [];
                $tokens = 0;
            }

            $current[] = $word;
            $tokens += $wordTokens;
        }

        if ($current !== []) {
            $parts[] = implode(' ', $current);
        }

        return $parts;
    }

    private function isHeading(string $paragraph): bool
    {
        if (str_contains($paragraph, "\n") || mb_strlen($paragraph) > 120 || preg_match('/\p{L}/u', $paragraph) !== 1) {
            return false;
        }

        $letters = preg_replace('/[^\p{L}]+/u', '', $paragraph) ?? '';

        return ($letters !== '' && mb_strtoupper($letters) === $letters)
            || preg_match('/^(?:\d+(?:\.\d+)*\s+)?[\p{Lu}][^.!?]{0,100}$/u', $paragraph) === 1;
    }

    private function isWorkedExampleStart(string $paragraph): bool
    {
        return preg_match('/^(?:worked\s+)?example\b/ui', $paragraph) === 1;
    }

    /** @return array{page: int, text: string, tokens: int, starts_section: bool} */
    private function unit(int $page, string $text, bool $startsSection): array
    {
        return [
            'page' => $page,
            'text' => $text,
            'tokens' => $this->estimateTokenCount($text),
            'starts_section' => $startsSection,
        ];
    }

    /**
     * @param  array<int, array{page: int, text: string, tokens: int, starts_section: bool}>  $units
     * @return array<int, array{page: int, text: string, tokens: int, starts_section: bool}>
     */
    private function overlapUnits(array $units): array
    {
        $overlap = [];
        $tokens = 0;

        for ($index = count($units) - 1; $index >= 0; $index--) {
            $unit = $units[$index];

            if ($unit['tokens'] > self::OVERLAP_TOKENS || $tokens + $unit['tokens'] > self::OVERLAP_TOKENS) {
                break;
            }

            array_unshift($overlap, $unit);
            $tokens += $unit['tokens'];
        }

        return $overlap;
    }

    /**
     * @param  array<int, array{page: int, text: string, tokens: int, starts_section: bool}>  $units
     * @return array{
     *     sequence: int,
     *     content: string,
     *     content_hash: string,
     *     estimated_token_count: int,
     *     metadata: array{page_start: int, page_end: int}
     * }
     */
    private function makeChunk(int $sequence, array $units): array
    {
        $content = trim(implode("\n\n", array_column($units, 'text')));

        return [
            'sequence' => $sequence,
            'content' => $content,
            'content_hash' => hash('sha256', $content),
            'estimated_token_count' => $this->estimateTokenCount($content),
            'metadata' => [
                'page_start' => min(array_column($units, 'page')),
                'page_end' => max(array_column($units, 'page')),
            ],
        ];
    }
}
