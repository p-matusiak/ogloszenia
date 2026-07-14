<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Enums\AdStatus;
use App\Exceptions\Domain\AdNotRefreshableException;
use App\Models\Ad;
use App\Support\AdPublicationWindow;

final readonly class RefreshAdAction
{
    public function __construct(private AdPublicationWindow $window) {}

    /**
     * @throws AdNotRefreshableException
     */
    public function execute(Ad $ad): Ad
    {
        if (! $ad->isRefreshable()) {
            throw new AdNotRefreshableException($ad->expires_at);
        }

        $ad->fill([
            'status' => AdStatus::Active,
            'deletion_warning_sent_at' => null,
        ] + $this->window->open());
        $ad->save();

        return $ad;
    }
}
