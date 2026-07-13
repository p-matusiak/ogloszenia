<?php

declare(strict_types=1);

namespace App\Enums;

enum AdSort: string
{
    case Relevance = 'relevance';
    case Newest = 'newest';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
