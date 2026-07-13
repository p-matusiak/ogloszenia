<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zanim kategorie dostały własne adresy, listing filtrował je parametrami
 * `?category=` i `?subcategory=`. Takie linki są w zakładkach użytkowników i w
 * indeksie wyszukiwarki, więc muszą oddawać 301 na nowy adres, a nie cicho
 * wyświetlać nieotagowaną stronę główną.
 */
final class RedirectLegacyCategoryFilters
{
    private const array LEGACY_PARAMS = ['subcategory', 'category'];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $this->deepestSlug($request);

        if ($slug === null || ! $request->routeIs('home')) {
            return $next($request);
        }

        /** @var array<string, mixed> $query */
        $query = $request->query();

        return redirect()->route(
            'categories.show',
            ['slug' => $slug] + Arr::except($query, self::LEGACY_PARAMS),
            Response::HTTP_MOVED_PERMANENTLY,
        );
    }

    /**
     * `subcategory` zawężało mocniej niż `category`, dokładnie tak jak dziś
     * czyta to `DatabaseAdSearchEngine`. Wygrywa więc węzeł bardziej szczegółowy.
     */
    private function deepestSlug(Request $request): ?string
    {
        foreach (self::LEGACY_PARAMS as $param) {
            $slug = $request->query($param);

            if (is_string($slug) && $slug !== '') {
                return $slug;
            }
        }

        return null;
    }
}
