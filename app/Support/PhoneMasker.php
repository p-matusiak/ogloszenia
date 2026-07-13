<?php

declare(strict_types=1);

namespace App\Support;

/**
 * „+48 600 123 456” → „+48 600 ••• •••”.
 *
 * Maska zostaje w payloadzie ogłoszenia, a pełny numer wydaje osobny,
 * limitowany endpoint. Dzięki temu jedno pobranie listy nie oddaje
 * scraperowi setek numerów naraz.
 */
final class PhoneMasker
{
    private const int VISIBLE_DIGITS = 5;

    private const string MASK_CHARACTER = '•';

    public function mask(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $seenDigits = 0;

        // Znaki formatujące (spacje, „+”, myślniki) zostają, żeby maska
        // zachowała kształt numeru i nie zdradzała, ile cyfr ukryto.
        return implode('', array_map(
            function (string $character) use (&$seenDigits): string {
                if (! ctype_digit($character)) {
                    return $character;
                }

                $seenDigits++;

                return $seenDigits <= self::VISIBLE_DIGITS ? $character : self::MASK_CHARACTER;
            },
            mb_str_split($phone),
        ));
    }
}
