<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Models\Ad;
use App\Search\Contracts\AdSearchEngine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Atrapa silnika wyszukiwania: nie dotyka bazy, zapamiętuje przekazane kryteria.
 * Dowodzi, że aplikacja zależy od kontraktu AdSearchEngine i że sterownik da się
 * podmienić — dokładnie tak, jak zrobi to przyszły silnik Elasticsearch.
 */
final class RecordingAdSearchEngine implements AdSearchEngine
{
    /** @var array<string, mixed> */
    public array $lastCriteria = [];

    /**
     * @param  array<string, mixed>  $criteria
     * @return LengthAwarePaginatorContract<int, Ad>
     */
    public function search(array $criteria): LengthAwarePaginatorContract
    {
        $this->lastCriteria = $criteria;

        /** @var LengthAwarePaginatorContract<int, Ad> $paginator */
        $paginator = new LengthAwarePaginator([], 0, 20, 1);

        return $paginator;
    }
}
