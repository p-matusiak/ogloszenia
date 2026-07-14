<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Events\AdDeletionWarningDue;
use App\Repositories\Contracts\AdRepository;

final readonly class WarnExpiredAdsDeletionAction
{
    public function __construct(private AdRepository $ads) {}

    /**
     * @return int Number of deletion-warning emails queued.
     */
    public function execute(): int
    {
        $due = $this->ads->listAdsDueForDeletionWarning();

        foreach ($due as $ad) {
            event(new AdDeletionWarningDue($ad));
            $this->ads->markDeletionWarningSent($ad);
        }

        return $due->count();
    }
}
