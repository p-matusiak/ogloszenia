<?php

declare(strict_types=1);

namespace App\Support;

final class AdContactAttributes
{
    /**
     * Numer na ogłoszeniu to wyłącznie nadpisanie profilu — null znaczy „użyj profilu”.
     *
     * @param  array<string, mixed>  $data
     */
    public static function overridePhone(array $data): ?string
    {
        if (! filter_var($data['use_custom_phone'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $phone = $data['contact_phone'] ?? null;

        return is_string($phone) && $phone !== '' ? $phone : null;
    }
}
