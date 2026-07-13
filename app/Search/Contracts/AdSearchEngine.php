<?php

declare(strict_types=1);

namespace App\Search\Contracts;

use App\Http\Requests\Ads\IndexAdRequest;
use App\Models\Ad;
use App\Search\Database\DatabaseAdSearchEngine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Granica wyszukiwania aktywnych ogłoszeń. Aplikacja (kontroler, zasoby, testy
 * HTTP) zależy wyłącznie od tego kontraktu, nigdy od konkretnego silnika —
 * dzięki temu bieżącą implementację relacyjną (DatabaseAdSearchEngine) można
 * później podmienić na Elasticsearch lub OpenSearch, zmieniając tylko binding
 * i konfigurację, bez ruszania warstwy HTTP.
 *
 * Kontrakt jest świadomie neutralny wobec silnika:
 * - wejście to zwykła tablica kryteriów — te same klucze, które produkuje
 *   {@see IndexAdRequest::filters()} — a nie Eloquent
 *   Builder ani DSL wyszukiwarki;
 * - wyjście to paginator modeli Ad. Implementacja Elasticsearcha odpytałaby
 *   indeks o pasujące identyfikatory i sumę trafień, po czym zhydratowała modele
 *   z bazy w kolejności trafień (wzorzec jak w Laravel Scout) i złożyła z nich
 *   ten sam paginator.
 *
 * @see DatabaseAdSearchEngine bieżąca implementacja
 */
interface AdSearchEngine
{
    /**
     * Strona aktywnych ogłoszeń pasujących do kryteriów, w kolejności wynikającej
     * z żądanego sortowania (trafność, data, cena).
     *
     * @param  array<string, mixed>  $criteria
     * @return LengthAwarePaginator<int, Ad>
     */
    public function search(array $criteria): LengthAwarePaginator;
}
