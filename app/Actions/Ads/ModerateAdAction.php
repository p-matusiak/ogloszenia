<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Support\AdPublicationWindow;

final readonly class ModerateAdAction
{
    public function __construct(private AdPublicationWindow $window) {}

    public function approve(Ad $ad): Ad
    {
        $ad->fill([
            'status' => AdStatus::Active,
            'rejection_reason' => null,
        ] + $this->window->open());

        $ad->save();

        return $ad;
    }

    public function reject(Ad $ad, string $reason): Ad
    {
        $ad->fill([
            'status' => AdStatus::Rejected,
            'rejection_reason' => $reason,
        ] + $this->window->closed());

        $ad->save();

        return $ad;
    }
}
