<?php

namespace App\Support;

class MathContent
{
    public static function normalize(?string $content): string
    {
        $content = trim((string) $content);
        if ($content === '') {
            return '';
        }

        $content = self::deduplicateRepeatedFormula($content);
        $content = self::normalizeExponents($content);
        $content = self::wrapEquationExpressions($content);
        $content = self::wrapCoefficientExpressions($content);

        return self::wrapSimpleFormulas($content);
    }

    private static function deduplicateRepeatedFormula(string $content): string
    {
        return preg_replace_callback(
            '/\b([A-Za-z][A-Za-z0-9]*(?:\s*[=+\-*\/]\s*[A-Za-z0-9^()+\-]+)+)\s*\1\b/',
            fn (array $matches) => $matches[1],
            $content,
        ) ?? $content;
    }

    private static function normalizeExponents(string $content): string
    {
        return preg_replace('/\b([A-Za-z0-9])\s*\^\s*([0-9]+)\b/', '$1^{$2}', $content) ?? $content;
    }

    private static function wrapCoefficientExpressions(string $content): string
    {
        return self::replaceOutsideMath($content, fn (string $segment): string => preg_replace_callback(
            '/\b(\d+\s*\([A-Za-z0-9+\-*\/\s]+\))/',
            fn (array $matches): string => '\\('.trim($matches[1]).'\\)',
            $segment,
        ) ?? $segment);
    }

    private static function wrapEquationExpressions(string $content): string
    {
        return self::replaceOutsideMath($content, fn (string $segment): string => preg_replace_callback(
            '/(?<![\\\\\w])([A-Za-z0-9][A-Za-z0-9^{}()]*(?:\s*[+\-*\/]\s*[A-Za-z0-9][A-Za-z0-9^{}()]*)*\s*=\s*[A-Za-z0-9][A-Za-z0-9^{}()]*(?:\s*[+\-*\/]\s*[A-Za-z0-9][A-Za-z0-9^{}()]*)*)(?![\w}])/',
            fn (array $matches): string => '\\('.trim($matches[1]).'\\)',
            $segment,
        ) ?? $segment);
    }

    private static function wrapSimpleFormulas(string $content): string
    {
        return self::replaceOutsideMath($content, fn (string $segment): string => preg_replace_callback(
            '/(?<!\\\\\()\b([A-Za-z][A-Za-z0-9]*(?:\s*[=+\-*\/]\s*[A-Za-z0-9^{}()+\-]+)+)\b(?!\\\\\))/',
            function (array $matches): string {
                $formula = trim($matches[1]);

                if (preg_match('/^[A-Za-z]+$/', str_replace(' ', '', $formula))) {
                    return $matches[0];
                }

                return '\\('.$formula.'\\)';
            },
            $segment,
        ) ?? $segment);
    }

    private static function replaceOutsideMath(string $content, callable $callback): string
    {
        $segments = preg_split('/(\\\\\(.+?\\\\\)|\\\\\[.+?\\\\\]|\$[^$]+\$)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($segments === false) {
            return $callback($content);
        }

        return implode('', array_map(
            fn (string $segment): string => self::isMathSegment($segment) ? $segment : $callback($segment),
            $segments,
        ));
    }

    private static function isMathSegment(string $segment): bool
    {
        return str_starts_with($segment, '\\(')
            || str_starts_with($segment, '\\[')
            || preg_match('/^\$[^$]+\$$/s', $segment) === 1;
    }
}
