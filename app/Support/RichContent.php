<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class RichContent
{
    /** @var array<string, string> */
    private static array $mathCache = [];

    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'span', 'div', 'sup', 'sub',
        'h3', 'h4', 'blockquote', 'code', 'pre', 'mark', 'a',
    ];

    private const MATH_TAGS = ['span', 'div'];
    private const ALIGNABLE_TAGS = ['p', 'h3', 'h4'];

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        $html = self::convertDelimitedMath($html);

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="rich-content-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('rich-content-root');
        if (! $root) {
            return '';
        }

        self::sanitizeNode($root);

        return trim(self::innerHtml($root));
    }

    private static function convertDelimitedMath(string $html): string
    {
        $html = preg_replace_callback(
            '/\\\\\[(.+?)\\\\\]/s',
            fn (array $matches) => '<div data-type="block-math" data-latex="'.htmlspecialchars(trim($matches[1]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"></div>',
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\\\\\((.+?)\\\\\)/s',
            fn (array $matches) => '<span data-type="inline-math" data-latex="'.htmlspecialchars(trim($matches[1]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"></span>',
            $html,
        ) ?? $html;

        return preg_replace_callback(
            '/\$(?!\d+\$)(.+?)\$(?!\d)/s',
            fn (array $matches) => '<span data-type="inline-math" data-latex="'.htmlspecialchars(trim($matches[1]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"></span>',
            $html,
        ) ?? $html;
    }

    public static function text(?string $html): string
    {
        $html = preg_replace_callback(
            '/<(span|div)[^>]*data-latex=(["\'])(.*?)\2[^>]*>.*?<\/\1>/is',
            fn (array $matches) => ' '.$matches[3].' ',
            (string) $html,
        ) ?? (string) $html;

        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $html))) ?? '');
    }

    public static function pdf(?string $html): string
    {
        $html = self::sanitize($html);
        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="rich-content-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('rich-content-root');
        if (! $root) {
            return $html;
        }

        $xpath = new DOMXPath($document);
        $nodes = iterator_to_array($xpath->query('//*[@data-type="inline-math" or @data-type="block-math"]') ?: []);
        if ($nodes === []) {
            return trim(self::innerHtml($root));
        }

        $expressions = [];
        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $latex = $node->getAttribute('data-latex');
            $displayMode = $node->getAttribute('data-type') === 'block-math';
            $key = ($displayMode ? 'block:' : 'inline:').$latex;

            if (! isset(self::$mathCache[$key])) {
                $expressions[$key] = [
                    'latex' => $latex,
                    'displayMode' => $displayMode,
                ];
            }
        }

        if ($expressions !== []) {
            self::$mathCache = [
                ...self::$mathCache,
                ...self::renderKatexBatch($expressions),
            ];
        }

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $latex = $node->getAttribute('data-latex');
            $displayMode = $node->getAttribute('data-type') === 'block-math';
            $key = ($displayMode ? 'block:' : 'inline:').$latex;
            $replacement = self::$mathCache[$key] ?? self::mathFallback($latex, $displayMode);

            self::replaceNodeWithHtml($document, $node, $replacement);
        }

        return trim(self::innerHtml($root));
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        for ($child = $node->firstChild; $child !== null; ) {
            $next = $child->nextSibling;

            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                        $child->parentNode?->removeChild($child);
                        $child = $next;

                        continue;
                    }

                    self::sanitizeNode($child);
                    self::unwrapNode($child);
                    $child = $next;

                    continue;
                }

                self::sanitizeAttributes($child, $tag);
            }

            self::sanitizeNode($child);
            $child = $next;
        }
    }

    /**
     * @param array<string, array{latex: string, displayMode: bool}> $expressions
     * @return array<string, string>
     */
    private static function renderKatexBatch(array $expressions): array
    {
        $script = self::projectPath('resources/js/render-katex.mjs');
        if (! is_file($script)) {
            return self::fallbackBatch($expressions);
        }

        $process = proc_open(
            'node '.escapeshellarg($script),
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            self::projectPath(),
        );

        if (! is_resource($process)) {
            return self::fallbackBatch($expressions);
        }

        fwrite($pipes[0], json_encode(array_values($expressions)) ?: '[]');
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $rendered = json_decode($output ?: '[]', true);
        if ($exitCode !== 0 || ! is_array($rendered)) {
            return self::fallbackBatch($expressions);
        }

        $result = [];
        foreach (array_keys($expressions) as $index => $key) {
            $result[$key] = is_string($rendered[$index] ?? null)
                ? $rendered[$index]
                : self::mathFallback($expressions[$key]['latex'], $expressions[$key]['displayMode']);
        }

        return $result;
    }

    /**
     * @param array<string, array{latex: string, displayMode: bool}> $expressions
     * @return array<string, string>
     */
    private static function fallbackBatch(array $expressions): array
    {
        $fallback = [];
        foreach ($expressions as $key => $expression) {
            $fallback[$key] = self::mathFallback($expression['latex'], $expression['displayMode']);
        }

        return $fallback;
    }

    private static function projectPath(string $path = ''): string
    {
        try {
            $basePath = function_exists('base_path') ? base_path() : getcwd();
        } catch (\Throwable) {
            $basePath = getcwd();
        }

        return rtrim((string) $basePath, DIRECTORY_SEPARATOR).($path !== '' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    private static function mathFallback(string $latex, bool $displayMode): string
    {
        $escaped = htmlspecialchars($latex, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return $displayMode
            ? '<div class="pdf-math-fallback">'.$escaped.'</div>'
            : '<span class="pdf-math-fallback">'.$escaped.'</span>';
    }

    private static function replaceNodeWithHtml(DOMDocument $document, DOMNode $node, string $html): void
    {
        $fragmentDocument = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $fragmentDocument->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="fragment-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $fragmentRoot = $fragmentDocument->getElementById('fragment-root');
        if (! $fragmentRoot || ! $node->parentNode) {
            return;
        }

        foreach (iterator_to_array($fragmentRoot->childNodes) as $child) {
            $node->parentNode->insertBefore($document->importNode($child, true), $node);
        }

        $node->parentNode->removeChild($node);
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = in_array($tag, self::MATH_TAGS, true) ? ['data-type', 'data-latex'] : [];
        if (in_array($tag, self::ALIGNABLE_TAGS, true)) {
            $allowed[] = 'style';
        }
        if ($tag === 'a') {
            array_push($allowed, 'href', 'target', 'rel');
        }

        for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
            $attribute = $element->attributes->item($index);
            if (! $attribute || ! in_array(strtolower($attribute->name), $allowed, true)) {
                if ($attribute) {
                    $element->removeAttribute($attribute->name);
                }
            }
        }

        if ($element->hasAttribute('style')) {
            self::sanitizeStyle($element);
        }

        if ($tag === 'a') {
            self::sanitizeLink($element);
        }

        if ($element->hasAttribute('data-type') && ! in_array($element->getAttribute('data-type'), ['inline-math', 'block-math'], true)) {
            $element->removeAttribute('data-type');
            $element->removeAttribute('data-latex');
        }
    }

    private static function sanitizeStyle(DOMElement $element): void
    {
        $style = strtolower($element->getAttribute('style'));
        if (preg_match('/text-align:\s*(left|center|right|justify)\s*;?/', $style, $matches)) {
            $element->setAttribute('style', 'text-align: '.$matches[1]);

            return;
        }

        $element->removeAttribute('style');
    }

    private static function sanitizeLink(DOMElement $element): void
    {
        $href = trim($element->getAttribute('href'));
        if ($href === '' || ! preg_match('/^(https?:\/\/|mailto:)/i', $href)) {
            $element->removeAttribute('href');
            $element->removeAttribute('target');
            $element->removeAttribute('rel');

            return;
        }

        $element->setAttribute('href', $href);
        $element->setAttribute('target', '_blank');
        $element->setAttribute('rel', 'noopener noreferrer');
    }

    private static function unwrapNode(DOMNode $node): void
    {
        $parent = $node->parentNode;
        if (! $parent) {
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }

    private static function innerHtml(DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }

        return $html;
    }
}
