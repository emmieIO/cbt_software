<?php

namespace App\Services;

class KnowledgeTextNormalizer
{
    public function normalize(ExtractedDocumentText $document): ExtractedDocumentText
    {
        $pages = array_map(
            fn (array $page): array => [
                'number' => $page['number'],
                'text' => $this->cleanCharacters($page['text']),
            ],
            $document->pages,
        );

        $pages = $this->removeRepeatedEdges($pages);
        $pages = array_values(array_filter(array_map(
            fn (array $page): array => [
                'number' => $page['number'],
                'text' => $this->normalizeLayout($page['text']),
            ],
            $pages,
        ), fn (array $page): bool => $page['text'] !== ''));

        return new ExtractedDocumentText(
            text: collect($pages)
                ->map(fn (array $page): string => '--- Page '.$page['number']." ---\n".$page['text'])
                ->implode("\n\n"),
            pages: $pages,
            method: $document->method,
        );
    }

    private function cleanCharacters(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r", "\u{00A0}", "\u{200B}", "\u{FEFF}"], ["\n", "\n", ' ', '', ''], $text);
        $text = str_replace(['\\“', '\\”', '\\‘', '\\’', '\\"', "\\'"], ['“', '”', '‘', '’', '"', "'"], $text);

        $lines = preg_split('/\n/u', $text) ?: [];

        return implode("\n", array_map(function (string $line): string {
            $line = preg_replace('/[\t ]+/u', ' ', $line) ?? $line;

            return trim($line);
        }, $lines));
    }

    /**
     * @param  array<int, array{number: int, text: string}>  $pages
     * @return array<int, array{number: int, text: string}>
     */
    private function removeRepeatedEdges(array $pages): array
    {
        if (count($pages) < 2) {
            return $pages;
        }

        $requiredOccurrences = max(2, (int) ceil(count($pages) * 0.6));
        $firstCounts = [];
        $lastCounts = [];

        foreach ($pages as $page) {
            $lines = $this->nonEmptyLines($page['text']);

            if ($lines === []) {
                continue;
            }

            $first = $this->canonicalEdge($lines[0]);
            $last = $this->canonicalEdge($lines[array_key_last($lines)]);
            $firstCounts[$first] = ($firstCounts[$first] ?? 0) + 1;
            $lastCounts[$last] = ($lastCounts[$last] ?? 0) + 1;
        }

        $repeatedHeaders = array_keys(array_filter($firstCounts, fn (int $count): bool => $count >= $requiredOccurrences));
        $repeatedFooters = array_keys(array_filter($lastCounts, fn (int $count): bool => $count >= $requiredOccurrences));

        return array_map(function (array $page) use ($repeatedHeaders, $repeatedFooters): array {
            $lines = preg_split('/\n/u', $page['text']) ?: [];
            $nonEmptyIndexes = array_keys(array_filter($lines, fn (string $line): bool => trim($line) !== ''));

            if ($nonEmptyIndexes !== []) {
                $firstIndex = $nonEmptyIndexes[0];
                $lastIndex = $nonEmptyIndexes[array_key_last($nonEmptyIndexes)];
                $first = $this->canonicalEdge($lines[$firstIndex]);
                $last = $this->canonicalEdge($lines[$lastIndex]);

                if (in_array($first, $repeatedHeaders, true)) {
                    $lines[$firstIndex] = '';
                }

                if (in_array($last, $repeatedFooters, true) || $this->isPageNumberFooter($lines[$lastIndex])) {
                    $lines[$lastIndex] = '';
                }
            }

            return ['number' => $page['number'], 'text' => implode("\n", $lines)];
        }, $pages);
    }

    private function normalizeLayout(string $text): string
    {
        $lines = preg_split('/\n/u', $text) ?: [];
        $paragraphs = [];
        $current = [];

        $flush = function () use (&$current, &$paragraphs): void {
            if ($current !== []) {
                $paragraphs[] = implode(' ', $current);
                $current = [];
            }
        };

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                $flush();

                continue;
            }

            if ($this->isStructuralLine($line)) {
                $flush();
                $paragraphs[] = $line;

                continue;
            }

            $current[] = $line;
        }

        $flush();

        return trim(implode("\n\n", array_values(array_filter($paragraphs))));
    }

    private function isStructuralLine(string $line): bool
    {
        if (preg_match('/^(?:worked\s+)?example\b|^solution\b|^exercise\b/ui', $line) === 1) {
            return true;
        }

        if (preg_match('/^(?:[-•]|\d+[.)]|[A-Za-z][.)])\s+/u', $line) === 1) {
            return true;
        }

        if (mb_strlen($line) <= 120 && preg_match('/\p{L}/u', $line) === 1) {
            $letters = preg_replace('/[^\p{L}]+/u', '', $line) ?? '';

            if ($letters !== '' && mb_strtoupper($letters) === $letters) {
                return true;
            }

            if (preg_match('/^(?:\d+(?:\.\d+)*\s+)?[\p{Lu}][^.!?]{0,100}$/u', $line) === 1) {
                return true;
            }
        }

        return mb_strlen($line) <= 100
            && preg_match('/(?:=|≤|≥|±|√|∑|∫|\^|[⁰¹²³⁴⁵⁶⁷⁸⁹])/', $line) === 1
            && preg_match('/[.!?]$/u', $line) !== 1;
    }

    /** @return array<int, string> */
    private function nonEmptyLines(string $text): array
    {
        return array_values(array_filter(
            preg_split('/\n/u', $text) ?: [],
            fn (string $line): bool => trim($line) !== '',
        ));
    }

    private function canonicalEdge(string $line): string
    {
        $line = preg_replace('/\s+/u', ' ', trim($line)) ?? trim($line);

        return mb_strtolower($line);
    }

    private function isPageNumberFooter(string $line): bool
    {
        return preg_match('/^(?:(?:page\s*)?\d+\s*(?:of|\/|\|)\s*\d+|(?:page\s*)?\d+)$/ui', trim($line)) === 1;
    }
}
