<?php

declare(strict_types=1);

use App\Http\Controllers\AdPageController;
use App\Http\Controllers\CategoryPageController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\SellerPageController;
use App\Http\Controllers\Seo\AdFeedController;
use App\Http\Controllers\Seo\RobotsController;
use App\Http\Controllers\Seo\SitemapController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

// `signed` rejects a tampered or expired link; the throttle stops someone
// brute-forcing the email hash of a known user id.
Route::get('/auth/{provider}/redirect', [OAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('oauth.redirect');

Route::get('/auth/{provider}/callback', [OAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'facebook'])
    ->name('oauth.callback');

Route::get('/email/weryfikacja/{id}/{hash}', EmailVerificationController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/feed.xml', AdFeedController::class)->name('feed');

// Wzorzec bez kropki, inaczej `{category}` połknęłoby też sufiks `.xml`.
Route::get('/feed/{category}.xml', AdFeedController::class)
    ->where('category', '[a-z0-9-]+')
    ->name('feed.category');

// Ogłoszenie ma własny kontroler: to jedyna strona budowana z danych z bazy,
// a przy okazji jedyna, która musi oddać 410 po wygaśnięciu i 301 ze starego
// sluga po zmianie tytułu.
Route::get('/ogloszenie/{slug}', AdPageController::class)->name('ads.show');

// Landing page kategorii. Adres płaski, bo `categories.slug` jest unikalny
// globalnie: przeniesienie gałęzi w drzewie nie unieważnia zaindeksowanego URL-a.
Route::get('/kategoria/{slug}', CategoryPageController::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('categories.show');

Route::get('/sprzedawca/{slug}', SellerPageController::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('sellers.show');

/**
 * Pozostałe trasy powłoki SPA. Wyliczone, a nie złapane catch-allem: wcześniej
 * każdy nieistniejący adres zwracał 200 z pustym `<div id="app">`, przez co
 * wyszukiwarka dostawała nieskończoną przestrzeń miękkich 404-ek.
 *
 * @var array<string, array{path: string, title: string|null, description: string, indexable: bool}> $pages
 */
$pages = Config::array('seo.pages');

foreach ($pages as $name => $page) {
    Route::get($page['path'], SpaController::class)->name($name);
}
