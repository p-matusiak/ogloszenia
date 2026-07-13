<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Dismissed = 'dismissed';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
