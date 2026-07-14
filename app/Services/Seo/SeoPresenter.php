<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Models\Ad;
use App\Models\Category;
use App\Models\User;
use App\Support\Seo\AdSeoText;
use App\Support\Seo\AdStructuredData;
use App\Support\Seo\CategorySeo;
use App\Support\Seo\PageMeta;
use App\Support\Seo\SiteUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

final readonly class SeoPresenter
{
    private const string PAGINATION_PARAM = 'page';

    private const string FIRST_PAGE = '1';

    public function __construct(
        private AdSeoText $text,
        private AdStructuredData $structuredData,
        private CategorySeo $category,
        private SiteUrl $siteUrl,
    ) {}

    /**
     * Wymaga kategorii z załadowaną relacją `ancestors`.
     */
    public function forCategory(Category $category, Request $request): PageMeta
    {
        return (new PageMeta(
            title: $this->withSiteName($this->paginated($this->category->title($category), $request)),
            description: $this->category->description($category),
            canonical: $this->canonical($request),
            indexable: true,
        ))
            ->withStructuredData($this->category->structuredData($category))
            ->withFeed(
                $this->siteUrl->route('feed.category', ['category' => $category->slug]),
                Config::string('seo.site_name').' — '.$category->name,
            );
    }

    /**
     * Wymaga ogłoszenia z załadowanymi relacjami `category` i `images`.
     */
    public function forAd(Ad $ad): PageMeta
    {
        $isVisible = $ad->isPubliclyVisible();

        $meta = (new PageMeta(
            title: $this->withSiteName($this->text->title($ad)),
            description: $this->text->description($ad),
            canonical: $this->siteUrl->route('ads.show', ['slug' => $ad->slug]),
            indexable: $isVisible,
        ))
            ->withOpenGraphType(PageMeta::TYPE_PRODUCT)
            ->withImage($ad->images->first()?->url());

        // Wygasłe albo jeszcze niezatwierdzone ogłoszenie nie ma czego obiecywać
        // wyszukiwarce: dane strukturalne z ceną „InStock” byłyby kłamstwem.
        return $isVisible
            ? $meta->withStructuredData($this->structuredData->build($ad))
            : $meta;
    }

    public function forSeller(User $seller): PageMeta
    {
        $description = $seller->bio !== null && $seller->bio !== ''
            ? Str::limit($seller->bio, 160)
            : 'Ogłoszenia sprzedawcy '.$seller->name.' w serwisie '.Config::string('seo.site_name').'.';

        return new PageMeta(
            title: $this->withSiteName('Ogłoszenia — '.$seller->name),
            description: $description,
            canonical: $this->siteUrl->route('sellers.show', ['slug' => $seller->slug]),
            indexable: true,
        );
    }

    public function forRequest(Request $request): PageMeta
    {
        $page = $this->page($request->route()?->getName());

        return new PageMeta(
            title: $this->withSiteName($this->paginated($page['title'], $request)),
            description: $page['description'],
            canonical: $this->canonical($request),
            indexable: $page['indexable'],
        );
    }

    /**
     * Meta dla powłoki SPA renderowanej poza kontrolerem — np. przez stronę
     * błędu 404. Nigdy nie jest indeksowana.
     */
    public function fallback(): PageMeta
    {
        return new PageMeta(
            title: $this->withSiteName(null),
            description: Config::string('seo.default_description'),
            canonical: $this->siteUrl->to('/'),
            indexable: false,
        );
    }

    /**
     * Druga strona listingu ma tę samą nazwę co pierwsza, a inną treść. Bez
     * numeru w tytule wyszukiwarka widzi dziesiątki identycznie zatytułowanych
     * stron i uznaje je za duplikaty.
     */
    private function paginated(?string $title, Request $request): ?string
    {
        $page = $request->query(self::PAGINATION_PARAM);

        if (! is_string($page) || ! ctype_digit($page) || (int) $page < 2) {
            return $title;
        }

        $suffix = 'strona '.$page;

        return $title === null ? Str::ucfirst($suffix) : $title.' – '.$suffix;
    }

    /**
     * Listing zmienia się wraz z 12 parametrami filtrów, ale treścią różni się
     * tylko numer strony — kategoria ma dziś własny adres. Reszta filtrów trafia
     * do canonical jako ten sam adres, więc robot nie indeksuje tysięcy wariantów.
     */
    private function canonical(Request $request): string
    {
        /** @var array<string, mixed> $query */
        $query = $request->query();

        /** @var list<string> $allowed */
        $allowed = Config::array('seo.canonical_query_params');

        $canonicalQuery = array_filter(
            Arr::only($query, $allowed),
            fn (mixed $value): bool => is_string($value) && $value !== '',
        );

        if (($canonicalQuery[self::PAGINATION_PARAM] ?? null) === self::FIRST_PAGE) {
            unset($canonicalQuery[self::PAGINATION_PARAM]);
        }

        ksort($canonicalQuery);

        $url = $this->siteUrl->to($request->getPathInfo());

        return $canonicalQuery === [] ? $url : $url.'?'.http_build_query($canonicalQuery);
    }

    private function withSiteName(?string $title): string
    {
        $siteName = Config::string('seo.site_name');

        return $title === null || $title === '' ? $siteName : $title.' | '.$siteName;
    }

    /**
     * @return array{title: string|null, description: string, indexable: bool}
     */
    private function page(?string $routeName): array
    {
        /** @var array<string, mixed> $page */
        $page = $routeName === null ? [] : Config::get('seo.pages.'.$routeName, []);

        $title = $page['title'] ?? null;
        $description = $page['description'] ?? Config::string('seo.default_description');

        return [
            'title' => is_string($title) ? $title : null,
            'description' => is_string($description) ? $description : '',
            'indexable' => ($page['indexable'] ?? false) === true,
        ];
    }
}
