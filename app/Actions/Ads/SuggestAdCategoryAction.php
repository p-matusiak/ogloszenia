<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Services\Contracts\AdCategorySuggester;

final readonly class SuggestAdCategoryAction
{
    public function __construct(private AdCategorySuggester $suggester) {}

    /**
     * @return array{category_id: int|null, available: bool}
     */
    public function execute(string $title): array
    {
        return [
            'category_id' => $this->suggester->suggestForTitle($title),
            'available' => $this->suggester->isAvailable(),
        ];
    }
}
