<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Services\Seo\SitemapUrlProvider;
use App\Support\Seo\XmlResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

final class SitemapController extends Controller
{
    private const string CACHE_KEY = 'seo:sitemap';

    private const string CONTENT_TYPE = 'application/xml';

    public function __construct(
        private readonly SitemapUrlProvider $urls,
        private readonly XmlResponse $xml,
    ) {}

    public function __invoke(): Response
    {
        /** @var string $body */
        $body = Cache::remember(
            self::CACHE_KEY,
            Config::integer('seo.cache_ttl'),
            fn (): string => $this->render(),
        );

        return $this->xml->make($body, self::CONTENT_TYPE);
    }

    private function render(): string
    {
        return view('seo.sitemap', [
            'staticPages' => $this->urls->staticPages(),
            'categories' => $this->urls->categories(),
            'sellers' => $this->urls->sellers(),
            'ads' => $this->urls->ads(),
        ])->render();
    }
}
