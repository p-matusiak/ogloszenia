<?php

declare(strict_types=1);

namespace App\Enums;

enum AdStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Deleted = 'deleted';

    public function isPubliclyVisible(): bool
    {
        return $this === self::Active;
    }

    /**
     * Rejected ads must be corrected and re-submitted; deleted ones are gone.
     * Only a live or lapsed ad can be pushed out for another 30 days.
     */
    public function isRefreshable(): bool
    {
        return $this === self::Active || $this === self::Expired;
    }

    public function isModeratable(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Ogłoszenie, które kiedyś było publiczne i już nie jest. Strona takiego
     * ogłoszenia oddaje 410 Gone, a nie 404: adres był poprawny, zasób zniknął
     * bezpowrotnie i wyszukiwarka może go od razu usunąć z indeksu.
     */
    public function isGone(): bool
    {
        return $this === self::Expired || $this === self::Deleted;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
