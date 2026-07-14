<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Events\AdWasExpired;
use App\Repositories\Contracts\AdRepository;

final readonly class ExpireAdsAction
{
    public function __construct(private AdRepository $ads) {}

    /**
     * @return int Number of ads moved out of the public listing.
     */
    public function execute(): int
    {
        $expired = $this->ads->expireDueActiveAds();

        foreach ($expired as $ad) {
            event(new AdWasExpired($ad));
        }

        return $expired->count();
    }
}
