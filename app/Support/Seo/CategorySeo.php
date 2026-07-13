<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Models\Category;

/**
 * Strona kategorii to landing page, na którym stoi SEO serwisu ogłoszeniowego:
 * zapytania brzmią „samochody osobowe”, a nie „ogłoszenie nr 4821”.
 *
 * Wymaga kategorii z załadowaną relacją `ancestors`.
 */
final class CategorySeo
{
    private const string HOME_LABEL = 'Strona główna';

    public function __construct(private readonly SiteUrl $siteUrl) {}

    public function title(Category $category): string
    {
        return $category->name.' — ogłoszenia';
    }

    public function description(Category $category): string
    {
        return sprintf(
            'Ogłoszenia w kategorii %s. Przeglądaj aktualne oferty, filtruj po cenie, stanie i lokalizacji.',
            $category->name,
        );
    }

    /**
     * BreadcrumbList to jedyne dane strukturalne, które Google pokazuje wprost
     * w wyniku wyszukiwania dla strony listingu. Ścieżkę przodków daje closure
     * table, posortowaną po `depth` rosnąco — czyli od najbliższego rodzica.
     *
     * @return array<string, mixed>
     */
    public function structuredData(Category $category): array
    {
        // Lista par, a nie mapa nazwa → adres: dwie kategorie o tej samej nazwie
        // w jednej ścieżce („Motoryzacja > Inne”, „Dom > Inne”) nadpisałyby się.
        $trail = [[self::HOME_LABEL, $this->siteUrl->route('home')]];

        foreach ($category->ancestors->reverse() as $ancestor) {
            $trail[] = [$ancestor->name, $this->url($ancestor)];
        }

        $trail[] = [$category->name, $this->url($category)];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $this->items($trail),
        ];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $trail
     * @return list<array<string, mixed>>
     */
    private function items(array $trail): array
    {
        $items = [];

        foreach ($trail as $index => [$name, $url]) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $name,
                'item' => $url,
            ];
        }

        return $items;
    }

    private function url(Category $category): string
    {
        return $this->siteUrl->route('categories.show', ['slug' => $category->slug]);
    }
}
