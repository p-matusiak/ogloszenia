<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\AdStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdSummaryResource;
use App\Models\Ad;
use App\Repositories\Contracts\AdRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AdMoreFromSellerController extends Controller
{
    private const int LIMIT = 4;

    public function __invoke(Ad $ad, AdRepository $ads): AnonymousResourceCollection
    {
        $this->authorize('view', $ad);

        if ($ad->status !== AdStatus::Active) {
            return AdSummaryResource::collection(collect());
        }

        return AdSummaryResource::collection(
            $ads->listActiveBySellerExcluding($ad->user_id, $ad->id, self::LIMIT),
        );
    }
}
