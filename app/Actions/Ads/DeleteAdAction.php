<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Enums\AdStatus;
use App\Models\Ad;

final class DeleteAdAction
{
    /**
     * Deletion is a status change, not a row removal: moderators must still be
     * able to inspect an ad that was pulled for breaking the rules.
     */
    public function execute(Ad $ad): void
    {
        $ad->update(['status' => AdStatus::Deleted]);
    }
}
