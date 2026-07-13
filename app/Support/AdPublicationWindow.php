<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;

/**
 * The single definition of "an ad is live for the next 30 days", shared by
 * creation, approval, refresh and re-submission.
 */
final class AdPublicationWindow
{
    /**
     * @return array{published_at: CarbonImmutable, expires_at: CarbonImmutable}
     */
    public function open(): array
    {
        // Immutable on purpose: a mutable Carbon would be shared by both keys,
        // and addDays() would silently shift published_at too.
        $now = CarbonImmutable::now();

        return [
            'published_at' => $now,
            'expires_at' => $now->addDays(Config::integer('ads.lifetime_days')),
        ];
    }

    /**
     * @return array{published_at: null, expires_at: null}
     */
    public function closed(): array
    {
        return ['published_at' => null, 'expires_at' => null];
    }
}
