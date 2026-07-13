<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Enums\AdStatus;
use App\Models\Ad;

final class ExpireAdsAction
{
    /**
     * @return int Number of ads moved out of the public listing.
     */
    public function execute(): int
    {
        return Ad::query()
            ->where('status', AdStatus::Active)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => AdStatus::Expired]);
    }
}
