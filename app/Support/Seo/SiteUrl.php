<?php

declare(strict_types=1);

namespace App\Support\Seo;

use Illuminate\Support\Facades\Config;

/**
 * Adresy kanoniczne buduje z `APP_URL`, a nie z hosta bieżącego żądania.
 *
 * `route()` czyta host z requestu, a sitemapa i kanały RSS są cache'owane
 * globalnie: kto pierwszy rozgrzeje cache, ten narzuca swoje adresy wszystkim.
 * Jedno wejście po `localhost` (health check, curl z hosta) zamroziłoby
 * localhostowe URL-e w pliku, który potem pobiera Googlebot. Ten sam adres musi
 * też trafiać do `<link rel="canonical">`, inaczej sitemapa zgłasza jeden URL,
 * a strona wskazuje na inny.
 */
final class SiteUrl
{
    public function to(string $path): string
    {
        return $this->root().'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, string>  $parameters
     */
    public function route(string $name, array $parameters = []): string
    {
        return $this->to(route($name, $parameters, absolute: false));
    }

    private function root(): string
    {
        return rtrim(Config::string('app.url'), '/');
    }
}
