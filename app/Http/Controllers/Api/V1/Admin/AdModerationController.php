<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Ads\ModerateAdAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectAdRequest;
use App\Http\Resources\AdResource;
use App\Models\Ad;

final class AdModerationController extends Controller
{
    public function approve(Ad $ad, ModerateAdAction $moderate): AdResource
    {
        return new AdResource(
            $moderate->approve($ad)->load(['category.ancestors', 'images', 'user']),
        );
    }

    public function reject(RejectAdRequest $request, Ad $ad, ModerateAdAction $moderate): AdResource
    {
        return new AdResource(
            $moderate->reject($ad, $request->string('reason')->toString())->load(['category.ancestors', 'images', 'user']),
        );
    }
}
