<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Repositories\Contracts\AdRepository;

final readonly class PurgeExpiredAdsAction
{
    public function __construct(private AdRepository $ads) {}

    /**
     * @return int Number of ads permanently removed from the owner's panel.
     */
    public function execute(): int
    {
        return $this->ads->purgeAdsPastRefreshGrace()->count();
    }
}
