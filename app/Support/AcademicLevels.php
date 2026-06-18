<?php

namespace App\Support;

class AcademicLevels
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function levelOptions(): array
    {
        return [
            ['value' => 'lp', 'label' => 'Lower Primary'],
            ['value' => 'hp', 'label' => 'Higher Primary'],
            ['value' => 'js', 'label' => 'Junior Secondary'],
            ['value' => 'ss', 'label' => 'Senior Secondary'],
        ];
    }

    /**
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public static function classOptions(): array
    {
        return [
            'lp' => self::primaryOptions(1, 3),
            'hp' => self::primaryOptions(4, 6),
            'js' => self::secondaryOptions('JSS', 7, 9),
            'ss' => self::secondaryOptions('SS', 10, 12),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function classOptionsFor(?string $level): array
    {
        return self::classOptions()[$level] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public static function classValues(): array
    {
        return collect(self::classOptions())
            ->flatten(1)
            ->pluck('value')
            ->all();
    }

    public static function classBelongsToLevel(?string $classLevel, ?string $level): bool
    {
        if (! $classLevel || ! $level) {
            return false;
        }

        return collect(self::classOptionsFor($level))
            ->contains(fn (array $option) => $option['value'] === $classLevel);
    }

    public static function defaultClassFor(?string $level): string
    {
        return self::classOptionsFor($level)[0]['value'] ?? '7';
    }

    public static function classLabel(?string $classLevel, ?string $level = null): ?string
    {
        if (! $classLevel) {
            return null;
        }

        $options = $level ? self::classOptionsFor($level) : collect(self::classOptions())->flatten(1)->all();

        return collect($options)->firstWhere('value', $classLevel)['label'] ?? null;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private static function primaryOptions(int $start, int $end): array
    {
        return collect(range($start, $end))
            ->map(fn (int $value) => ['value' => (string) $value, 'label' => 'Primary '.$value])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private static function secondaryOptions(string $prefix, int $start, int $end): array
    {
        return collect(range($start, $end))
            ->map(fn (int $value) => ['value' => (string) $value, 'label' => $prefix.' '.($value - $start + 1)])
            ->all();
    }
}
