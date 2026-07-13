<?php

declare(strict_types=1);

namespace App\Enums;

enum SettingKey: string
{
    case AutoApproveAds = 'auto_approve_ads';

    /**
     * Value applied when the row is missing from the settings table.
     */
    public function default(): bool
    {
        return match ($this) {
            self::AutoApproveAds => true,
        };
    }
}
