<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Ads\CreateAdAction;
use App\Actions\Ads\DeleteAdAction;
use App\Actions\Ads\RecordAdViewAction;
use App\Actions\Ads\UpdateAdAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\IndexAdRequest;
use App\Http\Requests\Ads\StoreAdRequest;
use App\Http\Requests\Ads\UpdateAdRequest;
use App\Http\Resources\AdResource;
use App\Http\Resources\AdSummaryResource;
use App\Models\Ad;
use App\Repositories\Contracts\AdRepository;
use App\Search\Contracts\AdSearchEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class AdsController extends Controller
{
    public function index(IndexAdRequest $request, AdSearchEngine $search): AnonymousResourceCollection
    {
        return AdSummaryResource::collection($search->search($request->filters()));
    }

    public function show(string $slug, Request $request, AdRepository $ads, RecordAdViewAction $recordView): AdResource
    {
        $ad = $ads->findDetailBySlug($slug);

        if ($ad === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($ad->isGone()) {
            abort(Response::HTTP_GONE);
        }

        $canView = $request->user()?->can('view', $ad) === true;

        if (! $ad->isPubliclyVisible() && ! $canView) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $recordView->execute($ad);

        return new AdResource($ad);
    }

    public function store(StoreAdRequest $request, CreateAdAction $createAd): JsonResponse
    {
        $ad = $createAd->execute(
            user: $request->author(),
            data: $request->safe()->except(['images', 'temporary_images']),
            images: $request->images(),
            temporaryImages: $request->temporaryImages(),
        );

        return (new AdResource($ad->load(['category.ancestors', 'images', 'user'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateAdRequest $request, Ad $ad, UpdateAdAction $updateAd): AdResource
    {
        $updated = $updateAd->execute(
            ad: $ad,
            data: $request->safe()->except(['images', 'temporary_images']),
            newImages: $request->images(),
            temporaryImages: $request->temporaryImages(),
        );

        return new AdResource($updated->load(['category.ancestors', 'images', 'user']));
    }

    public function destroy(Ad $ad, DeleteAdAction $deleteAd): Response
    {
        $this->authorize('delete', $ad);

        $deleteAd->execute($ad);

        return response()->noContent();
    }
}
