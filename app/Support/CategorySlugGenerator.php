<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Category;
use App\Models\CategorySlugHistory;
use Illuminate\Support\Str;

final class CategorySlugGenerator
{
    public function generate(string $name, ?int $ignoreCategoryId = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'kategoria';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->isTaken($slug, $ignoreCategoryId)) {
            $slug = sprintf('%s-%d', $base, $suffix++);
        }

        return $slug;
    }

    /**
     * Zajęty jest też slug, pod którym stała kiedyś inna kategoria: ponowne
     * użycie takiego adresu przekierowałoby stary, zaindeksowany link na
     * zupełnie inną gałąź drzewa.
     */
    private function isTaken(string $slug, ?int $ignoreCategoryId): bool
    {
        $takenByCategory = Category::query()
            ->when($ignoreCategoryId !== null, fn ($query) => $query->whereKeyNot($ignoreCategoryId))
            ->where('slug', $slug)
            ->exists();

        if ($takenByCategory) {
            return true;
        }

        return CategorySlugHistory::query()
            ->where('slug', $slug)
            ->when($ignoreCategoryId !== null, fn ($query) => $query->where('category_id', '!=', $ignoreCategoryId))
            ->exists();
    }
}
