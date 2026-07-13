<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Ads\RefreshAdAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdResource;
use App\Models\Ad;

final class AdRefreshController extends Controller
{
    public function __invoke(Ad $ad, RefreshAdAction $refreshAd): AdResource
    {
        $this->authorize('refresh', $ad);

        $refreshed = $refreshAd->execute($ad);

        return new AdResource($refreshed->load(['category.ancestors', 'images', 'user']));
    }
}
