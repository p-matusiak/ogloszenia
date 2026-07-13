<?php

declare(strict_types=1);

use App\Models\Category;

it('renders a landing page with title, canonical and breadcrumb structured data', function (): void {
    $root = Category::factory()->create(['slug' => 'motoryzacja', 'name' => 'Motoryzacja']);
    Category::factory()->childOf($root)->create(['slug' => 'samochody', 'name' => 'Samochody']);

    $response = $this->get('/kategoria/samochody')->assertOk();

    $response->assertSee('<title>Samochody — ogłoszenia | '.config('seo.site_name').'</title>', false);
    $response->assertSee('rel="canonical" href="'.route('categories.show', ['slug' => 'samochody']).'"', false);
    $response->assertSee('name="robots" content="index, follow"', false);
    $response->assertSee('"@type":"BreadcrumbList"', false);
});

it('builds the breadcrumb from the closure table, root first', function (): void {
    $root = Category::factory()->create(['slug' => 'dom', 'name' => 'Dom']);
    $mid = Category::factory()->childOf($root)->create(['slug' => 'meble', 'name' => 'Meble']);
    Category::factory()->childOf($mid)->create(['slug' => 'sofy', 'name' => 'Sofy']);

    $response = $this->get('/kategoria/sofy')->assertOk();

    $response->assertSee('"position":1,"name":"Strona główna"', false);
    $response->assertSee('"position":2,"name":"Dom"', false);
    $response->assertSee('"position":3,"name":"Meble"', false);
    $response->assertSee('"position":4,"name":"Sofy"', false);
});

it('offers the category rss channel next to the global one', function (): void {
    Category::factory()->create(['slug' => 'rowery']);

    $this->get('/kategoria/rowery')
        ->assertOk()
        ->assertSee('href="'.route('feed.category', ['category' => 'rowery']).'"', false)
        ->assertSee('href="'.route('feed').'"', false);
});

it('numbers the title of a paginated listing so the pages are not duplicates', function (): void {
    Category::factory()->create(['slug' => 'rowery', 'name' => 'Rowery']);

    $this->get('/kategoria/rowery?page=3')
        ->assertOk()
        ->assertSee('<title>Rowery — ogłoszenia – strona 3 | '.config('seo.site_name').'</title>', false)
        ->assertSee('rel="canonical" href="'.route('categories.show', ['slug' => 'rowery']).'?page=3"', false);
});

it('drops filter noise from the canonical url', function (): void {
    Category::factory()->create(['slug' => 'rowery']);

    // Sortowanie i cena pokazują tę samą treść w innej kolejności — jeden adres.
    $this->get('/kategoria/rowery?sort=price_asc&price_min=100&page=1')
        ->assertOk()
        ->assertSee('rel="canonical" href="'.route('categories.show', ['slug' => 'rowery']).'"', false);
});

it('answers 404 for a hidden or unknown category', function (): void {
    Category::factory()->hidden()->create(['slug' => 'ukryta']);

    $this->get('/kategoria/ukryta')->assertNotFound();
    $this->get('/kategoria/nie-ma-takiej')->assertNotFound();
});
