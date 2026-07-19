<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Ad;
use App\Search\Contracts\AdSearchEngine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface AdRepository
{
    public function findDetailBySlug(string $slug): ?Ad;

    public function findByHistoricalSlug(string $slug): ?Ad;

    /**
     * Lista moderatora: wszystkie statusy, opcjonalnie zawężone do jednego.
     * Publiczne wyszukiwanie aktywnych ogłoszeń obsługuje osobny kontrakt
     * {@see AdSearchEngine}.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Ad>
     */
    public function paginateForModeration(array $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Ad;

    public function save(Ad $ad): Ad;

    public function markAsDeleted(Ad $ad): void;

    public function incrementViews(Ad $ad): void;

    public function countCreatedTodayForUser(int $userId): int;

    /**
     * Aktywne ogłoszenia sprzedawcy na sekcję „inne od tego sprzedawcy”.
     *
     * @return Collection<int, Ad>
     */
    public function listActiveBySellerExcluding(int $sellerId, int $excludeAdId, int $limit): Collection;

    public function softDeleteAllOwnedByUser(int $userId): int;

    /**
     * Aktywne ogłoszenia po terminie `expires_at` przenoszone do statusu expired.
     *
     * @return SupportCollection<int, Ad>
     */
    public function expireDueActiveAds(): SupportCollection;

    /**
     * Wygasłe ogłoszenia, dla których minął termin ostrzeżenia przed usunięciem.
     *
     * @return SupportCollection<int, Ad>
     */
    public function listAdsDueForDeletionWarning(): SupportCollection;

    public function markDeletionWarningSent(Ad $ad): void;

    /**
     * Wygasłe ogłoszenia po okresie na odświeżenie — oznaczane jako usunięte.
     *
     * @return SupportCollection<int, Ad>
     */
    public function purgeAdsPastRefreshGrace(): SupportCollection;
}
