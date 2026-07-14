<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Models\Ad;
use App\Models\Category;
use App\Models\User;
use App\Support\Seo\SiteUrl;
use Generator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\LazyCollection;

/**
 * Adresy, które mają trafić do indeksu. Każdy z nich musi być tym samym adresem,
 * na który wskazuje `<link rel="canonical">` docelowej strony — sitemapa nie może
 * zgłaszać URL-i, których strona się wypiera.
 */
final readonly class SitemapUrlProvider
{
    private const string CHANGEFREQ_DAILY = 'daily';

    private const string CHANGEFREQ_WEEKLY = 'weekly';

    public function __construct(private SiteUrl $siteUrl) {}

    /**
     * @return list<array<string, string|null>>
     */
    public function staticPages(): array
    {
        /** @var array<string, array{path: string, indexable: bool}> $pages */
        $pages = Config::array('seo.pages');

        $urls = [];

        foreach ($pages as $name => $page) {
            if (! $page['indexable']) {
                continue;
            }

            $urls[] = $this->entry($this->siteUrl->route($name), null, self::CHANGEFREQ_WEEKLY);
        }

        return $urls;
    }

    /**
     * @return list<array<string, string|null>>
     */
    public function categories(): array
    {
        return Category::query()
            ->visible()
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->get()
            ->map(fn (Category $category): array => $this->entry(
                $this->siteUrl->route('categories.show', ['slug' => $category->slug]),
                $category->updated_at?->toAtomString(),
                self::CHANGEFREQ_DAILY,
            ))
            ->all();
    }

    /**
     * @return list<array<string, string|null>>
     */
    public function sellers(): array
    {
        return User::query()
            ->whereHas('activeAds')
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->get()
            ->map(fn (User $seller): array => $this->entry(
                $this->siteUrl->route('sellers.show', ['slug' => $seller->slug]),
                $seller->updated_at?->toAtomString(),
                self::CHANGEFREQ_WEEKLY,
            ))
            ->all();
    }

    /**
     * Strumieniowo: przy 45 000 ogłoszeń zmaterializowanie kolekcji modeli
     * zjadłoby setki megabajtów, a sitemapa i tak jest renderowana liniowo.
     *
     * @return LazyCollection<int, array<string, string|null>>
     */
    public function ads(): LazyCollection
    {
        return LazyCollection::make(function (): Generator {
            $ads = Ad::query()
                ->published()
                ->select(['id', 'slug', 'updated_at'])
                ->orderByDesc('published_at')
                ->limit(Config::integer('seo.sitemap.max_ads'))
                ->cursor();

            foreach ($ads as $ad) {
                yield $this->entry(
                    $this->siteUrl->route('ads.show', ['slug' => $ad->slug]),
                    $ad->updated_at?->toAtomString(),
                    self::CHANGEFREQ_DAILY,
                );
            }
        });
    }

    /**
     * @return array<string, string|null>
     */
    private function entry(string $loc, ?string $lastmod, string $changefreq): array
    {
        return ['loc' => $loc, 'lastmod' => $lastmod, 'changefreq' => $changefreq];
    }
}
