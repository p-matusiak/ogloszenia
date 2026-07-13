<?php

declare(strict_types=1);

namespace App\Enums;

enum AdCondition: string
{
    case New = 'new';
    case Used = 'used';
    case Damaged = 'damaged';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
