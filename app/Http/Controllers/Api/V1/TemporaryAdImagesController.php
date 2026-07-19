<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\StoreTemporaryAdImageRequest;
use App\Support\TemporaryAdImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TemporaryAdImagesController extends Controller
{
    public function store(StoreTemporaryAdImageRequest $request, TemporaryAdImageStorage $storage): JsonResponse
    {
        $uploads = array_map(
            fn ($image) => $storage->storeForUser($request->author(), $image),
            $request->images(),
        );

        return response()->json(['data' => $uploads], Response::HTTP_CREATED);
    }

    public function destroy(string $token, Request $request, TemporaryAdImageStorage $storage): Response
    {
        $user = $request->user();
        assert($user !== null);

        $storage->deleteForUser($user, $token);

        return response()->noContent();
    }
}
