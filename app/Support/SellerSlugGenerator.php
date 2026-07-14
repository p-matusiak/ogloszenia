<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Models\UserSlugHistory;
use Illuminate\Support\Str;

/**
 * Buduje SEO slug profilu sprzedawcy, np. "techpoint-anna-kowalska".
 */
final class SellerSlugGenerator
{
    private const int MAX_LENGTH = 120;

    public function generate(string $name, ?int $ignoreUserId = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'sprzedawca';
        }

        $base = trim(Str::limit($base, self::MAX_LENGTH - 4, ''), '-');
        $slug = $base;
        $suffix = 2;

        while ($this->isTaken($slug, $ignoreUserId)) {
            $slug = sprintf('%s-%d', $base, $suffix++);
        }

        return $slug;
    }

    /**
     * Zajęty jest też slug, pod którym stał kiedyś inny profil — ponowne użycie
     * przekierowałoby zaindeksowany adres na cudze ogłoszenia.
     */
    private function isTaken(string $slug, ?int $ignoreUserId): bool
    {
        $takenByUser = User::query()
            ->when($ignoreUserId !== null, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->where('slug', $slug)
            ->exists();

        if ($takenByUser) {
            return true;
        }

        return UserSlugHistory::query()
            ->where('slug', $slug)
            ->when($ignoreUserId !== null, fn ($query) => $query->where('user_id', '!=', $ignoreUserId))
            ->exists();
    }
}
