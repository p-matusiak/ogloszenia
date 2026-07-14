<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface AdCategorySuggester
{
    public function isAvailable(): bool;

    public function suggestForTitle(string $title): ?int;
}
