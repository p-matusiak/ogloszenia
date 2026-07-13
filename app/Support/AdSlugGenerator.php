<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Ad;
use App\Models\AdSlugHistory;
use Illuminate\Support\Str;

/**
 * Builds the SEO slug behind /ogloszenie/{slug}, e.g. "iphone-13-128gb-warszawa".
 */
final class AdSlugGenerator
{
    private const int MAX_LENGTH = 180;

    private const int MAX_ATTEMPTS = 10;

    public function generate(string $title, ?string $location = null, ?int $ignoreAdId = null): string
    {
        $base = $this->base($title, $location);
        $candidate = $base;

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            if (! $this->isTaken($candidate, $ignoreAdId)) {
                return $candidate;
            }

            $candidate = $base.'-'.Str::lower(Str::random(6));
        }

        // Astronomically unlikely; a ULID is guaranteed unique rather than probable.
        return $base.'-'.Str::lower((string) Str::ulid());
    }

    private function base(string $title, ?string $location): string
    {
        $base = Str::slug($title);

        if ($location !== null && $location !== '') {
            $base .= '-'.Str::slug($location);
        }

        // Leave room for a "-xxxxxx" disambiguating suffix inside the column.
        return trim(Str::limit($base, self::MAX_LENGTH - 30, ''), '-');
    }

    /**
     * Zajęty jest też każdy slug, pod którym cudze ogłoszenie kiedyś stało:
     * ponowne użycie takiego adresu przekierowałoby stary, zaindeksowany link
     * na zupełnie inną ofertę.
     */
    private function isTaken(string $slug, ?int $ignoreAdId): bool
    {
        $takenByAd = Ad::query()
            ->where('slug', $slug)
            ->when($ignoreAdId !== null, fn ($query) => $query->whereKeyNot($ignoreAdId))
            ->exists();

        if ($takenByAd) {
            return true;
        }

        return AdSlugHistory::query()
            ->where('slug', $slug)
            ->when($ignoreAdId !== null, fn ($query) => $query->where('ad_id', '!=', $ignoreAdId))
            ->exists();
    }
}
