<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Ad;
use App\Models\User;
use App\Repositories\Contracts\FavoriteRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

final class EloquentFavoriteRepository implements FavoriteRepository
{
    /**
     * @var list<string>
     */
    private const array LISTING_RELATIONS = ['category.ancestors', 'primaryImage'];

    public function add(User $user, Ad $ad): void
    {
        $user->favoriteAds()->syncWithoutDetaching([$ad->id]);
    }

    public function remove(User $user, Ad $ad): void
    {
        $user->favoriteAds()->detach($ad->id);
    }

    /**
     * @return LengthAwarePaginatorContract<int, Ad>
     */
    public function paginateActiveForUser(User $user): LengthAwarePaginatorContract
    {
        // @var: relacja belongsToMany dokłada `pivot` do modelu (Ad&object{pivot}),
        // co nie jest podtypem Ad przy niekowariantnym szablonie paginatora.
        /** @var LengthAwarePaginatorContract<int, Ad> $paginator */
        $paginator = $user->favoriteAds()
            ->published()
            ->with(self::LISTING_RELATIONS)
            ->orderByPivot('created_at', 'desc')
            ->paginate(Config::integer('ads.per_page'));

        return $paginator;
    }

    /**
     * @return list<int>
     */
    public function activeFavoriteIdsFor(User $user): array
    {
        return $user->favoriteAds()
            ->published()
            ->pluck('ads.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    public function usersFavoriting(Ad $ad): Collection
    {
        return $ad->favoritedByUsers()->get();
    }
}
