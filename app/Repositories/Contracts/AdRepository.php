<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Ad;
use App\Search\Contracts\AdSearchEngine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdRepository
{
    /**
     * Lista moderatora: wszystkie statusy, opcjonalnie zawężone do jednego.
     * Publiczne wyszukiwanie aktywnych ogłoszeń obsługuje osobny kontrakt
     * {@see AdSearchEngine}.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Ad>
     */
    public function paginateForModeration(array $filters): LengthAwarePaginator;
}
