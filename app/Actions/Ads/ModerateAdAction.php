<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Enums\AdStatus;
use App\Events\AdWasActivated;
use App\Models\Ad;
use App\Repositories\Contracts\AdRepository;
use App\Support\AdPublicationWindow;

final readonly class ModerateAdAction
{
    public function __construct(
        private AdRepository $ads,
        private AdPublicationWindow $window,
    ) {}

    public function approve(Ad $ad): Ad
    {
        $ad->fill([
            'status' => AdStatus::Active,
            'rejection_reason' => null,
        ] + $this->window->open());

        $saved = $this->ads->save($ad);

        event(new AdWasActivated($saved));

        return $saved;
    }

    public function reject(Ad $ad, string $reason): Ad
    {
        $ad->fill([
            'status' => AdStatus::Rejected,
            'rejection_reason' => $reason,
        ] + $this->window->closed());

        return $this->ads->save($ad);
    }
}
