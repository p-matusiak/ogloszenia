<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\Category;

it('lists only active ads', function (): void {
    Ad::factory()->create(['title' => 'Widoczne ogloszenie']);
    Ad::factory()->pending()->create();
    Ad::factory()->rejected()->create();
    Ad::factory()->expired()->create();
    Ad::factory()->deleted()->create();

    $this->getJson('/api/v1/ads')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Widoczne ogloszenie');
});

it('finds ads by words in the title or the description', function (): void {
    Ad::factory()->create(['title' => 'Sprzedam rower gorski', 'description' => 'Stan idealny, malo uzywany rower.']);
    Ad::factory()->create(['title' => 'Sprzedam pralke', 'description' => 'Sprawna pralka automatyczna.']);

    $this->getJson('/api/v1/ads?q=rower')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Sprzedam rower gorski');

    $this->getJson('/api/v1/ads?q=automatyczna')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Sprzedam pralke');
});

it('survives punctuation in the search box', function (): void {
    Ad::factory()->create(['title' => 'Sprzedam rower']);

    // websearch_to_tsquery must swallow stray operators rather than error.
    $this->getJson('/api/v1/ads?q='.urlencode('rower & | ! <->'))->assertOk();
});

it('rolls ads up from a subcategory into its parent category', function (): void {
    $motoryzacja = Category::factory()->create(['slug' => 'motoryzacja']);
    $samochody = Category::factory()->childOf($motoryzacja)->create(['slug' => 'samochody']);
    $motocykle = Category::factory()->childOf($motoryzacja)->create(['slug' => 'motocykle']);
    $praca = Category::factory()->create(['slug' => 'praca']);

    Ad::factory()->in($samochody)->create();
    Ad::factory()->in($motocykle)->create();
    Ad::factory()->in(leafCategory($praca))->create();

    $this->getJson('/api/v1/ads?category=motoryzacja')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/ads?category=samochody')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/ads?category=praca')->assertOk()->assertJsonCount(1, 'data');
});

it('lets the subcategory filter narrow the category filter', function (): void {
    $root = Category::factory()->create(['slug' => 'motoryzacja']);
    $samochody = Category::factory()->childOf($root)->create(['slug' => 'samochody']);
    Category::factory()->childOf($root)->create(['slug' => 'motocykle']);

    Ad::factory()->in($samochody)->create();
    Ad::factory()->in(Category::query()->where('slug', 'motocykle')->sole())->create();

    $this->getJson('/api/v1/ads?category=motoryzacja&subcategory=samochody')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('shows the newest and most recently refreshed ads first', function (): void {
    Ad::factory()->create(['title' => 'Starsze', 'published_at' => now()->subDays(5)]);
    Ad::factory()->create(['title' => 'Nowsze', 'published_at' => now()->subDay()]);

    $this->getJson('/api/v1/ads')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Nowsze')
        ->assertJsonPath('data.1.title', 'Starsze');
});

it('does not run a query per ad when listing', function (): void {
    Ad::factory()->count(5)->create();

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->getJson('/api/v1/ads')->assertOk();

    // Ads, count, category, ancestors, primary image: a fixed budget that must
    // not scale with the number of ads on the page.
    expect($queries)->toBeLessThanOrEqual(6);
});
