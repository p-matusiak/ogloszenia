<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Models\Ad;
use App\Repositories\Contracts\AdRepository;

final class RecordAdViewAction
{
    public function __construct(private readonly AdRepository $ads) {}

    /**
     * Model::increment() issues `views_count = views_count + 1` in SQL, so
     * concurrent views cannot lose counts, and it syncs the in-memory
     * attribute so the response body does not serialise a stale value.
     */
    public function execute(Ad $ad): void
    {
        $this->ads->incrementViews($ad);
    }
}
