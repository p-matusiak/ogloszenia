<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Favorites\AddFavoriteAction;
use App\Actions\Favorites\RemoveFavoriteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Favorites\FavoriteAdRequest;
use App\Http\Resources\AdSummaryResource;
use App\Models\Ad;
use App\Models\User;
use App\Repositories\Contracts\FavoriteRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class FavoritesController extends Controller
{
    public function index(Request $request, FavoriteRepository $favorites): AnonymousResourceCollection
    {
        return AdSummaryResource::collection(
            $favorites->paginateActiveForUser($this->currentUser($request)),
        );
    }

    public function ids(Request $request, FavoriteRepository $favorites): JsonResponse
    {
        return response()->json([
            'data' => $favorites->activeFavoriteIdsFor($this->currentUser($request)),
        ]);
    }

    public function store(FavoriteAdRequest $request, Ad $ad, AddFavoriteAction $action): Response
    {
        $action->execute($this->currentUser($request), $ad);

        return response()->noContent();
    }

    public function destroy(Request $request, Ad $ad, RemoveFavoriteAction $action): Response
    {
        $action->execute($this->currentUser($request), $ad);

        return response()->noContent();
    }

    private function currentUser(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
