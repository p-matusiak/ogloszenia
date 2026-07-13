<?php

declare(strict_types=1);

use App\Search\Database\DatabaseAdSearchEngine;

return [
    /*
     * Silnik wyszukiwania aktywnych ogłoszeń. Aplikacja zależy od kontraktu
     * App\Search\Contracts\AdSearchEngine; ten klucz decyduje, którą implementację
     * zbinduje SearchServiceProvider. Dziś jedyny sterownik to relacyjna baza.
     *
     * Dołożenie Elasticsearcha/OpenSearcha nie ruszy kontrolerów: wystarczy
     * dopisać klasę silnika do mapy `drivers` i ustawić SEARCH_DRIVER.
     */
    'driver' => env('SEARCH_DRIVER', 'database'),

    'drivers' => [
        'database' => DatabaseAdSearchEngine::class,
        // 'elasticsearch' => \App\Search\Elasticsearch\ElasticsearchAdSearchEngine::class,
    ],
];
