<?php

declare(strict_types=1);

namespace App\Providers;

use App\Search\Contracts\AdSearchEngine;
use App\Search\Database\DatabaseAdSearchEngine;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class SearchServiceProvider extends ServiceProvider
{
    /**
     * Kontrakt wyszukiwania rozwiązujemy przez sterownik z config/search.php.
     * Podmiana silnika (np. na Elasticsearch) to zmiana SEARCH_DRIVER i wpisu
     * w mapie `search.drivers` — bez ruszania kodu, który wstrzykuje kontrakt.
     */
    public function register(): void
    {
        $this->app->bind(AdSearchEngine::class, function (Application $app): AdSearchEngine {
            $driver = (string) config('search.driver', 'database');
            $drivers = (array) config('search.drivers', []);
            $engine = $drivers[$driver] ?? DatabaseAdSearchEngine::class;

            $instance = $app->make(is_string($engine) ? $engine : DatabaseAdSearchEngine::class);
            assert($instance instanceof AdSearchEngine);

            return $instance;
        });
    }
}
