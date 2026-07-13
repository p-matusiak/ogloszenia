<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Category;
use App\Support\Seo\AdSeoText;
use App\Support\Seo\SiteUrl;
use App\Support\Seo\XmlResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Kanał RSS 2.0 z aktywnymi ogłoszeniami — globalny albo zawężony do poddrzewa
 * jednej kategorii. Zawężenie idzie przez closure table (`inCategoryTree`), więc
 * „Motoryzacja” obejmuje też wszystko spod „Samochodów”.
 */
final class AdFeedController extends Controller
{
    private const string CONTENT_TYPE = 'application/rss+xml';

    public function __construct(
        private readonly AdSeoText $text,
        private readonly SiteUrl $siteUrl,
        private readonly XmlResponse $xml,
    ) {}

    public function __invoke(?Category $category = null): Response
    {
        if ($category !== null && ! $category->is_visible) {
            abort(Response::HTTP_NOT_FOUND);
        }

        /** @var string $body */
        $body = Cache::remember(
            'seo:feed:'.($category === null ? 'all' : $category->slug),
            Config::integer('seo.cache_ttl'),
            fn (): string => $this->render($category),
        );

        return $this->xml->make($body, self::CONTENT_TYPE);
    }

    private function render(?Category $category): string
    {
        $ads = Ad::query()
            ->published()
            ->when($category, fn ($query, Category $scoped) => $query->inCategoryTree($scoped->slug))
            ->with(['category', 'primaryImage'])
            ->orderByDesc('published_at')
            ->limit(Config::integer('seo.feed.limit'))
            ->get();

        return view('seo.feed', [
            'ads' => $ads,
            'text' => $this->text,
            'siteUrl' => $this->siteUrl,
            'title' => $this->channelTitle($category),
            'selfUrl' => $category === null
                ? $this->siteUrl->route('feed')
                : $this->siteUrl->route('feed.category', ['category' => $category->slug]),
            'lastBuildDate' => $this->lastBuildDate($ads),
        ])->render();
    }

    private function channelTitle(?Category $category): string
    {
        $siteName = Config::string('seo.site_name');

        return $category === null
            ? $siteName.' — najnowsze ogłoszenia'
            : $siteName.' — '.$category->name;
    }

    /**
     * Ogłoszenia przychodzą od najnowszego, więc pierwsza data publikacji jest
     * jednocześnie najświeższa. Pusty kanał datujemy „teraz”, bo `lastBuildDate`
     * jest w RSS obowiązkowe dla czytników sprawdzających świeżość.
     *
     * @param  Collection<int, Ad>  $ads
     */
    private function lastBuildDate(Collection $ads): string
    {
        foreach ($ads as $ad) {
            if ($ad->published_at !== null) {
                return $ad->published_at->toRssString();
            }
        }

        return now()->toRssString();
    }
}
