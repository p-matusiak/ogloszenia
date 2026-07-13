<?php

declare(strict_types=1);

namespace App\Enums;

enum DeliveryMethod: string
{
    case Personal = 'personal';
    case Courier = 'courier';
    case ParcelLocker = 'parcel_locker';
    case Post = 'post';
    case Local = 'local';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
