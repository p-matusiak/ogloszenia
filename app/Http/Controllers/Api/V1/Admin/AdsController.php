<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Ads\DeleteAdAction;
use App\Enums\AdStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdResource;
use App\Http\Resources\AdSummaryResource;
use App\Models\Ad;
use App\Repositories\Contracts\AdRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

final class AdsController extends Controller
{
    public function index(Request $request, AdRepository $ads): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(AdStatus::class)],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        return AdSummaryResource::collection($ads->paginateForModeration($filters));
    }

    public function show(Ad $ad): AdResource
    {
        return new AdResource($ad->load(['category.ancestors', 'images', 'user']));
    }

    public function destroy(Ad $ad, DeleteAdAction $deleteAd): Response
    {
        $deleteAd->execute($ad);

        return response()->noContent();
    }
}
