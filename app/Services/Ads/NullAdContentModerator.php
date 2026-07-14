<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Services\Contracts\AdContentModerator;

final class NullAdContentModerator implements AdContentModerator
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function review(string $title, string $description): AdModerationResult
    {
        return AdModerationResult::unavailable();
    }
}
