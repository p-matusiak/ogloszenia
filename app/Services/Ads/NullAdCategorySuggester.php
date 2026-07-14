<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Services\Contracts\AdCategorySuggester;

final class NullAdCategorySuggester implements AdCategorySuggester
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function suggestForTitle(string $title): ?int
    {
        return null;
    }
}
