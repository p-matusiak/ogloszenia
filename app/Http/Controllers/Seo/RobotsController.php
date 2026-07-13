<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Support\Seo\SiteUrl;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;

/**
 * Generowany, a nie statyczny: adres sitemapy musi pochodzić z `APP_URL`,
 * inaczej po zmianie domeny plik cicho wskazuje w pustkę.
 */
final class RobotsController extends Controller
{
    public function __invoke(SiteUrl $siteUrl): Response
    {
        /** @var list<string> $disallow */
        $disallow = Config::array('seo.robots.disallow');

        $lines = ['User-agent: *', 'Allow: /'];

        foreach ($disallow as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.$siteUrl->route('sitemap');

        return response(
            implode("\n", $lines)."\n",
            Response::HTTP_OK,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
