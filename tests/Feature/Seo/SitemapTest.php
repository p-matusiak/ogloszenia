<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\Category;

it('serves xml listing active ads, visible categories and indexable pages', function (): void {
    $root = Category::factory()->create(['slug' => 'motoryzacja']);
    $leaf = Category::factory()->childOf($root)->create(['slug' => 'samochody']);

    Ad::factory()->in($leaf)->create(['slug' => 'sprzedam-fiata']);

    $response = $this->get('/sitemap.xml')->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('application/xml; charset=UTF-8');

    $response->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);
    $response->assertSee('<loc>'.route('ads.show', ['slug' => 'sprzedam-fiata']).'</loc>', false);
    $response->assertSee('<loc>'.route('categories.show', ['slug' => 'motoryzacja']).'</loc>', false);
    $response->assertSee('<loc>'.route('terms').'</loc>', false);
});

it('gives every level of the tree its own url, not a query-string variant', function (): void {
    $root = Category::factory()->create(['slug' => 'dom']);
    Category::factory()->childOf($root)->create(['slug' => 'meble']);

    // Sitemapa nie może zgłaszać adresu innego niż canonical docelowej strony.
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('<loc>'.route('categories.show', ['slug' => 'dom']).'</loc>', false)
        ->assertSee('<loc>'.route('categories.show', ['slug' => 'meble']).'</loc>', false)
        ->assertDontSee('?category=', false);
});

it('leaves out ads and categories that the public must not reach', function (): void {
    Ad::factory()->pending()->create(['slug' => 'oczekujace']);
    Ad::factory()->expired()->create(['slug' => 'wygasle']);
    Ad::factory()->lapsed()->create(['slug' => 'przeterminowane']);
    Category::factory()->hidden()->create(['slug' => 'ukryta']);

    $response = $this->get('/sitemap.xml')->assertOk();

    $response->assertDontSee('oczekujace', false);
    $response->assertDontSee('wygasle', false);
    $response->assertDontSee('przeterminowane', false);
    $response->assertDontSee('ukryta', false);
});

it('builds urls from APP_URL, not from the host that happened to warm the cache', function (): void {
    config(['app.url' => 'https://ogloszenia.example']);
    Ad::factory()->create(['slug' => 'sprzedam-fiata']);

    // Żądanie leci po „localhost”, a plik jest cache'owany globalnie. Gdyby URL-e
    // brały się z requestu, jedno wejście curl-em zamroziłoby localhostowe adresy
    // w sitemapie, którą potem pobiera Googlebot.
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('<loc>https://ogloszenia.example/ogloszenie/sprzedam-fiata</loc>', false)
        ->assertDontSee('localhost', false);
});

it('keeps pages behind authentication out of the sitemap', function (): void {
    $response = $this->get('/sitemap.xml')->assertOk();

    $response->assertDontSee(route('admin'), false);
    $response->assertDontSee(route('login'), false);
    $response->assertDontSee(route('ads.create'), false);
});
