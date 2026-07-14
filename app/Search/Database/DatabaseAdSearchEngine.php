<?php

declare(strict_types=1);

namespace App\Search\Database;

use App\Enums\AdSort;
use App\Models\Ad;
use App\Search\Contracts\AdSearchEngine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Relacyjny silnik wyszukiwania: filtruje, sortuje i stronicuje aktywne
 * ogłoszenia wyłącznie przez Eloquent. To domyślna, jedyna dziś implementacja
 * {@see AdSearchEngine}; docelowo obok niej stanie ElasticsearchAdSearchEngine.
 */
final class DatabaseAdSearchEngine implements AdSearchEngine
{
    /**
     * Strona listy to kilka zapytań, nie 61.
     *
     * @var list<string>
     */
    private const array LISTING_RELATIONS = ['category.ancestors', 'primaryImage'];

    /**
     * Filtry bez pokrywającego indeksu — dokładny COUNT(*) po nich seq-skanuje
     * całą tabelę, więc powyżej progu liczymy je szacunkiem planisty.
     *
     * @var list<string>
     */
    private const array SEQ_SCAN_FILTERS = ['q', 'location', 'delivery', 'condition'];

    /**
     * @param  array<string, mixed>  $criteria
     * @return LengthAwarePaginatorContract<int, Ad>
     */
    public function search(array $criteria): LengthAwarePaginatorContract
    {
        // published() jest obowiązkowy: publiczny listing nigdy nie pokazuje
        // pending / rejected / expired / deleted ani wygasłych aktywnych.
        $query = $this->filtered($criteria)->published();
        $perPage = Config::integer('ads.per_page');
        $page = LengthAwarePaginator::resolveCurrentPage();

        $items = $this->sorted($query->clone(), $criteria)
            ->with(self::LISTING_RELATIONS)
            ->forPage($page, $perPage)
            ->get();

        return (new LengthAwarePaginator(
            $items,
            $this->cachedTotal($criteria, $query),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page'],
        ))->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return Builder<Ad>
     */
    private function filtered(array $criteria): Builder
    {
        $query = Ad::query();

        $this->applyTextFilters($query, $criteria);
        $this->applyGeoFilters($query, $criteria);
        $this->applyPriceFilters($query, $criteria);
        $this->applyAttributeFilters($query, $criteria);

        return $query;
    }

    /**
     * `subcategory` zawęża mocniej niż `category`; gdy przyjdą oba, wygrywa
     * węzeł bardziej szczegółowy, a każdy z nich obejmuje całe swoje poddrzewo.
     *
     * @param  Builder<Ad>  $query
     * @param  array<string, mixed>  $criteria
     */
    private function applyTextFilters(Builder $query, array $criteria): void
    {
        $categorySlug = $criteria['subcategory'] ?? $criteria['category'] ?? null;

        $query
            ->when(
                filled($criteria['q'] ?? null),
                fn (Builder $builder) => $builder->matching((string) $criteria['q']),
            )
            ->when(
                filled($categorySlug),
                fn (Builder $builder) => $builder->inCategoryTree((string) $categorySlug),
            )
            ->when(
                filled($criteria['location'] ?? null) && ! $this->hasGeoFilter($criteria),
                // ILIKE, bo lokalizacja to swobodny tekst wpisany przez autora.
                fn (Builder $builder) => $builder->where('location', 'ilike', '%'.$criteria['location'].'%'),
            );
    }

    /**
     * Promień od punktu — PostGIS ST_DWithin + GiST. Gdy przychodzą współrzędne,
     * tekstowy filtr location jest pomijany, bo geo jest precyzyjniejsze.
     *
     * @param  Builder<Ad>  $query
     * @param  array<string, mixed>  $criteria
     */
    private function applyGeoFilters(Builder $query, array $criteria): void
    {
        if (! $this->hasGeoFilter($criteria)) {
            return;
        }

        $query->withinRadius(
            (float) $criteria['lat'],
            (float) $criteria['lng'],
            (float) $criteria['radius_km'],
        );
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function hasGeoFilter(array $criteria): bool
    {
        return isset($criteria['lat'], $criteria['lng'], $criteria['radius_km'])
            && is_numeric($criteria['lat'])
            && is_numeric($criteria['lng'])
            && is_numeric($criteria['radius_km']);
    }

    /**
     * @param  Builder<Ad>  $query
     * @param  array<string, mixed>  $criteria
     */
    private function applyPriceFilters(Builder $query, array $criteria): void
    {
        $query
            ->when(
                isset($criteria['price_min']),
                fn (Builder $builder) => $builder->where('price', '>=', $criteria['price_min']),
            )
            ->when(
                isset($criteria['price_max']),
                fn (Builder $builder) => $builder->where('price', '<=', $criteria['price_max']),
            )
            ->when(
                // „Za darmo” to cena zero, a nie brak ceny.
                ($criteria['free'] ?? null) === true,
                fn (Builder $builder) => $builder->where('price', '=', 0),
            );
    }

    /**
     * @param  Builder<Ad>  $query
     * @param  array<string, mixed>  $criteria
     */
    private function applyAttributeFilters(Builder $query, array $criteria): void
    {
        $query
            ->when(
                ($criteria['negotiable'] ?? null) === true,
                fn (Builder $builder) => $builder->where('is_negotiable', true),
            )
            ->when(
                filled($criteria['condition'] ?? null),
                fn (Builder $builder) => $builder->whereIn('condition', $this->list($criteria['condition'])),
            )
            ->when(
                filled($criteria['delivery'] ?? null),
                fn (Builder $builder) => $this->applyDeliveryFilter($builder, $this->list($criteria['delivery'])),
            )
            ->when(
                filled($criteria['seller'] ?? null),
                fn (Builder $builder) => $builder->whereRelation('user', 'slug', (string) $criteria['seller']),
            );
    }

    /**
     * Dowolna z wybranych metod dostawy wystarczy. Każde `@>` trafia w indeks
     * GIN jsonb_path_ops, a Postgres łączy trafienia przez BitmapOr.
     *
     * @param  Builder<Ad>  $query
     * @param  list<string>  $methods
     * @return Builder<Ad>
     */
    private function applyDeliveryFilter(Builder $query, array $methods): Builder
    {
        return $query->where(function (Builder $builder) use ($methods): void {
            foreach ($methods as $method) {
                $builder->orWhereJsonContains('delivery_methods', $method);
            }
        });
    }

    /**
     * Sortowanie bez NULLS LAST: `published()` gwarantuje status='active', a
     * constraint ads_active_published_at_present gwarantuje, że takie wiersze
     * mają published_at. Dzięki temu ORDER BY trafia w indeks zamiast sortować
     * cały zbiór. Ogłoszenia bez ceny trzyma na końcu kolumna has_price.
     *
     * @param  Builder<Ad>  $query
     * @param  array<string, mixed>  $criteria
     * @return Builder<Ad>
     */
    private function sorted(Builder $query, array $criteria): Builder
    {
        return match ($this->resolveSort($criteria)) {
            AdSort::Relevance => $query->orderByRelevance((string) $criteria['q']),
            AdSort::Newest => $query->orderByDesc('published_at'),
            AdSort::PriceAsc => $query->orderByDesc('has_price')->orderBy('price'),
            AdSort::PriceDesc => $query->orderByDesc('has_price')->orderByDesc('price'),
        };
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function resolveSort(array $criteria): AdSort
    {
        $requested = AdSort::tryFrom((string) ($criteria['sort'] ?? ''));

        if ($requested === AdSort::Relevance && ! filled($criteria['q'] ?? null)) {
            return AdSort::Newest;
        }

        if ($requested !== null) {
            return $requested;
        }

        return filled($criteria['q'] ?? null) ? AdSort::Relevance : AdSort::Newest;
    }

    /**
     * Dokładny COUNT(*) na kilku milionach ogłoszeń to sekundy, a paginator woła
     * go przy każdym żądaniu. Licznik jest afordancją UI, nie danymi księgowymi,
     * więc wygasa po TTL zamiast być inwalidowany przy każdym zapisie.
     *
     * @param  array<string, mixed>  $criteria
     * @param  Builder<Ad>  $query
     */
    private function cachedTotal(array $criteria, Builder $query): int
    {
        $ttl = Config::integer('ads.count_cache_ttl');

        if ($ttl <= 0) {
            return $this->resolveTotal($criteria, $query);
        }

        return (int) Cache::remember(
            $this->totalCacheKey($criteria),
            $ttl,
            fn (): int => $this->resolveTotal($criteria, $query),
        );
    }

    /**
     * Filtry pokryte indeksem (kategoria, cena, free, brak filtra) liczymy
     * dokładnie — Index Only Scan zwraca prawdziwą liczbę w milisekundach, a dla
     * poddrzewa kategorii estymator planisty i tak myli się o rzędy wielkości.
     * Filtry bez indeksu (delivery, condition, negotiable, location, q; geo ma
     * GiST) zmuszają
     * COUNT(*) do Seq Scanu całej sterty — kilka sekund niezależnie od liczby
     * trafień. Tam powyżej progu zwracamy szacunek planisty (błąd rzędu ułamka
     * procenta); małe zbiory nadal liczymy dokładnie, bo liczba jest wtedy
     * istotna dla użytkownika, a sam licznik tani.
     *
     * @param  array<string, mixed>  $criteria
     * @param  Builder<Ad>  $query
     */
    private function resolveTotal(array $criteria, Builder $query): int
    {
        $threshold = Config::integer('ads.count_estimate_threshold');

        if ($threshold <= 0 || $this->exactCountIsCheap($criteria)) {
            return $query->clone()->count();
        }

        $estimate = $this->estimatedTotal($query);

        if ($estimate !== null && $estimate >= $threshold) {
            return $estimate;
        }

        return $query->clone()->count();
    }

    /**
     * Czy zbiór filtrów da się policzyć Index Only Scanem. Sprawdzamy realne
     * zastosowanie filtra, nie samą obecność klucza: filters() zawsze dokłada
     * negotiable/free jako boolean, a filled(false) to prawda — więc obecność
     * klucza nie znaczy, że filtr działa. Filtry tekstowe działają, gdy niepuste;
     * negotiable, gdy jawnie true (tak samo warunkuje je zapytanie).
     *
     * @param  array<string, mixed>  $criteria
     */
    private function exactCountIsCheap(array $criteria): bool
    {
        if (($criteria['negotiable'] ?? null) === true) {
            return false;
        }

        foreach (self::SEQ_SCAN_FILTERS as $key) {
            if (filled($criteria[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Liczba wierszy, jaką planista spodziewa się dopasować — czyta ją z
     * EXPLAIN, więc kosztuje samo planowanie (~2 ms), bez wykonania zapytania.
     * Zwraca null, gdy formatu planu nie da się odczytać; wołający sięga wtedy
     * po dokładny licznik.
     *
     * @param  Builder<Ad>  $query
     */
    private function estimatedTotal(Builder $query): ?int
    {
        // toBase(): explain() na bazowym builderze zwraca surowe wiersze planu,
        // a nie kolekcję typowaną na model Ad. Bierzemy węzeł szczytowy przez
        // all()[0] — ->first() wywołałby regułę noUnnecessaryCollectionCall.
        $rows = $query->clone()->reorder()->toBase()->explain()->all();
        $row = (array) ($rows[0] ?? []);
        $plan = (string) ($row['QUERY PLAN'] ?? '');

        if (preg_match('/\brows=(\d+)/', $plan, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Sortowanie i numer strony nie zmieniają liczności zbioru.
     *
     * @param  array<string, mixed>  $criteria
     */
    private function totalCacheKey(array $criteria): string
    {
        $relevant = Arr::except($criteria, ['sort', 'page']);
        ksort($relevant);

        return 'ads:published:count:'.md5(json_encode($relevant, JSON_THROW_ON_ERROR));
    }

    /**
     * Filtry wielokrotnego wyboru przychodzą jako lista rozdzielona przecinkami.
     *
     * @return list<string>
     */
    private function list(mixed $value): array
    {
        return array_values(array_filter(explode(',', (string) $value)));
    }
}
