<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Services\Contracts\AdCategorySuggester;

final class FakeAdCategorySuggester implements AdCategorySuggester
{
    public function __construct(
        private readonly bool $available = true,
        private readonly ?int $categoryId = null,
    ) {}

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function suggestForTitle(string $title): ?int
    {
        return $this->available ? $this->categoryId : null;
    }
}
