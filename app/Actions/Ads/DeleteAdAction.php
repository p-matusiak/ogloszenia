<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Models\Ad;
use App\Repositories\Contracts\AdRepository;

final class DeleteAdAction
{
    public function __construct(private readonly AdRepository $ads) {}

    /**
     * Deletion is a status change, not a row removal: moderators must still be
     * able to inspect an ad that was pulled for breaking the rules.
     */
    public function execute(Ad $ad): void
    {
        $this->ads->markAsDeleted($ad);
    }
}
