<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\AdSlugHistory;
use App\Repositories\Contracts\AdRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Config;

final class EloquentAdRepository implements AdRepository
{
    /**
     * Strona listy to kilka zapytań, nie 61.
     *
     * @var list<string>
     */
    private const array LISTING_RELATIONS = ['category.ancestors', 'primaryImage'];

    /** Karty powiązanych ogłoszeń nie pokazują kategorii — sam primaryImage wystarczy. */
    private const array RELATED_RELATIONS = ['primaryImage'];

    /** Szczegół ogłoszenia musi od razu mieć komplet danych do API. */
    private const array DETAIL_RELATIONS = ['category.ancestors', 'images', 'user'];

    public function findDetailBySlug(string $slug): ?Ad
    {
        return Ad::query()
            ->with(self::DETAIL_RELATIONS)
            ->where('slug', $slug)
            ->first();
    }

    public function findByHistoricalSlug(string $slug): ?Ad
    {
        $history = AdSlugHistory::query()
            ->with('ad')
            ->where('slug', $slug)
            ->first();

        return $history?->ad;
    }

    /**
     * Lista moderatora obejmuje wszystkie statusy (aktywne, oczekujące,
     * odrzucone), więc nie przechodzi przez silnik wyszukiwania aktywnych
     * ogłoszeń. Admin filtruje wyłącznie po statusie i frazie.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginatorContract<int, Ad>
     */
    public function paginateForModeration(array $filters): LengthAwarePaginatorContract
    {
        $status = $filters['status'] ?? null;
        $term = $filters['q'] ?? null;

        return Ad::query()
            ->when(
                filled($term),
                fn (Builder $query) => $query->matching((string) $term),
            )
            ->when(
                filled($status),
                fn (Builder $query) => $query->where('status', $status),
            )
            ->with(self::LISTING_RELATIONS)
            ->latest('created_at')
            ->paginate(Config::integer('ads.per_page'))
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Ad
    {
        return Ad::query()->create($attributes);
    }

    public function save(Ad $ad): Ad
    {
        $ad->save();

        return $ad->refresh();
    }

    public function markAsDeleted(Ad $ad): void
    {
        $ad->update(['status' => AdStatus::Deleted]);
    }

    public function incrementViews(Ad $ad): void
    {
        $ad->increment('views_count');
    }

    public function countCreatedTodayForUser(int $userId): int
    {
        return Ad::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    /**
     * @return Collection<int, Ad>
     */
    public function listActiveBySellerExcluding(int $sellerId, int $excludeAdId, int $limit): Collection
    {
        return Ad::query()
            ->where('user_id', $sellerId)
            ->whereKeyNot($excludeAdId)
            ->published()
            ->with(self::RELATED_RELATIONS)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function softDeleteAllOwnedByUser(int $userId): int
    {
        $query = Ad::query()->where('user_id', $userId);

        $count = (clone $query)->count();

        if ($count === 0) {
            return 0;
        }

        $query->update([
            'status' => AdStatus::Deleted,
            'updated_at' => now(),
        ]);

        Ad::query()
            ->where('user_id', $userId)
            ->delete();

        return $count;
    }

    public function expireDueActiveAds(): SupportCollection
    {
        $ads = Ad::query()
            ->where('status', AdStatus::Active)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with('user')
            ->get();

        foreach ($ads as $ad) {
            $ad->status = AdStatus::Expired;
            $ad->save();
        }

        return $ads;
    }

    public function listAdsDueForDeletionWarning(): SupportCollection
    {
        $graceDays = Config::integer('ads.refresh_grace_days');
        $warningDays = Config::integer('ads.deletion_warning_days');
        $warningThreshold = now()->subDays($graceDays - $warningDays);

        return Ad::query()
            ->where('status', AdStatus::Expired)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $warningThreshold)
            ->whereNull('deletion_warning_sent_at')
            ->with('user')
            ->get();
    }

    public function markDeletionWarningSent(Ad $ad): void
    {
        $ad->deletion_warning_sent_at = now();
        $ad->save();
    }

    public function purgeAdsPastRefreshGrace(): SupportCollection
    {
        $graceDays = Config::integer('ads.refresh_grace_days');
        $deletionThreshold = now()->subDays($graceDays);

        $ads = Ad::query()
            ->where('status', AdStatus::Expired)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $deletionThreshold)
            ->get();

        foreach ($ads as $ad) {
            $ad->update(['status' => AdStatus::Deleted]);
        }

        return $ads;
    }
}
