<?php

declare(strict_types=1);

namespace App\Enums;

enum AppLocale: string
{
    case Polish = 'pl';
    case English = 'en';
    case Russian = 'ru';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function tryFromHeader(string $header): ?self
    {
        $candidates = array_map(
            static fn (string $part): string => strtolower(trim(explode(';', $part)[0])),
            explode(',', $header),
        );

        foreach ($candidates as $candidate) {
            $primary = explode('-', $candidate)[0];

            $locale = self::tryFrom($primary);

            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }
}
