<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Ad;
use App\Repositories\Contracts\AdRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

final class EloquentAdRepository implements AdRepository
{
    /**
     * Strona listy to kilka zapytań, nie 61.
     *
     * @var list<string>
     */
    private const array LISTING_RELATIONS = ['category.ancestors', 'primaryImage'];

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
}
