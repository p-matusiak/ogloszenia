<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;

it('serves an rss 2.0 channel of the newest active ads', function (): void {
    $ad = Ad::factory()->create([
        'title' => 'Sprzedam laptopa Dell',
        'slug' => 'sprzedam-laptopa-dell',
        'price' => 2500.00,
        'location' => 'Krakow',
        'is_negotiable' => false,
    ]);

    $response = $this->get('/feed.xml')->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('application/rss+xml; charset=UTF-8');

    $response->assertSee('<rss version="2.0"', false);
    $response->assertSee('<title>Sprzedam laptopa Dell</title>', false);
    $response->assertSee('<guid isPermaLink="true">'.route('ads.show', ['slug' => $ad->slug]).'</guid>', false);
    $response->assertSee('rel="self"', false);
    // Cena i lokalizacja przed opisem — to one decydują o kliknięciu w czytniku.
    $response->assertSee('500,00 zł · Krakow —', false);
});

it('publishes the primary image as an enclosure with its real size and mime type', function (): void {
    $ad = Ad::factory()->create(['slug' => 'z-obrazkiem']);
    AdImage::factory()->for($ad)->create(['path' => 'ads/foto.webp', 'size_bytes' => 123_456]);

    $this->get('/feed.xml')
        ->assertOk()
        ->assertSee('length="123456"', false)
        ->assertSee('type="image/webp"', false);
});

it('never leaks an ad that is not publicly visible', function (): void {
    Ad::factory()->create(['title' => 'Aktywne ogloszenie']);
    Ad::factory()->pending()->create(['title' => 'Oczekujace ogloszenie']);
    Ad::factory()->expired()->create(['title' => 'Wygasle ogloszenie']);
    Ad::factory()->lapsed()->create(['title' => 'Przeterminowane ogloszenie']);

    $this->get('/feed.xml')
        ->assertOk()
        ->assertSee('Aktywne ogloszenie', false)
        ->assertDontSee('Oczekujace ogloszenie', false)
        ->assertDontSee('Wygasle ogloszenie', false)
        ->assertDontSee('Przeterminowane ogloszenie', false);
});

it('scopes a category feed to the whole subtree below it', function (): void {
    $root = Category::factory()->create(['slug' => 'motoryzacja', 'name' => 'Motoryzacja']);
    $leaf = Category::factory()->childOf($root)->create();
    $other = Category::factory()->childOf(Category::factory()->create())->create();

    Ad::factory()->in($leaf)->create(['title' => 'Fiat z poddrzewa']);
    Ad::factory()->in($other)->create(['title' => 'Pralka z innego drzewa']);

    $this->get('/feed/motoryzacja.xml')
        ->assertOk()
        ->assertSee('Fiat z poddrzewa', false)
        ->assertDontSee('Pralka z innego drzewa', false);
});

it('answers 404 for a hidden or unknown category feed', function (): void {
    Category::factory()->hidden()->create(['slug' => 'ukryta']);

    $this->get('/feed/ukryta.xml')->assertNotFound();
    $this->get('/feed/nie-ma-takiej.xml')->assertNotFound();
});

it('announces the feed from the html shell so readers can discover it', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('type="application/rss+xml"', false)
        ->assertSee('href="'.route('feed').'"', false);
});
