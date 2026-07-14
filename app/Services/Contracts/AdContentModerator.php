<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Services\Ads\AdModerationResult;

interface AdContentModerator
{
    public function isAvailable(): bool;

    public function review(string $title, string $description): AdModerationResult;
}
