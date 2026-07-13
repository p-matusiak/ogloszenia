<?php

declare(strict_types=1);

use App\Models\Ad;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

it('reuses the cached total instead of counting on every request', function (): void {
    Ad::factory()->count(3)->create();

    $this->getJson('/api/v1/ads')->assertOk()->assertJsonPath('meta.total', 3);

    Ad::factory()->create();

    // Licznik żyje przez TTL, więc czwarte ogłoszenie jeszcze go nie rusza,
    // mimo że sama strona pokazuje już cztery pozycje.
    $this->getJson('/api/v1/ads')
        ->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonCount(4, 'data');
});

it('counts separately for each set of filters', function (): void {
    Ad::factory()->count(2)->create(['location' => 'Warszawa']);
    Ad::factory()->create(['location' => 'Gdańsk']);

    $this->getJson('/api/v1/ads')->assertOk()->assertJsonPath('meta.total', 3);
    $this->getJson('/api/v1/ads?location=Gdańsk')->assertOk()->assertJsonPath('meta.total', 1);
});

it('ignores sort and page when keying the cached total', function (): void {
    Ad::factory()->count(3)->create();

    $this->getJson('/api/v1/ads')->assertOk()->assertJsonPath('meta.total', 3);

    $counts = 0;
    DB::listen(function ($query) use (&$counts): void {
        if (str_contains($query->sql, 'count(*)')) {
            $counts++;
        }
    });

    $this->getJson('/api/v1/ads?sort=price_asc')->assertOk()->assertJsonPath('meta.total', 3);
    $this->getJson('/api/v1/ads?page=1')->assertOk()->assertJsonPath('meta.total', 3);

    expect($counts)->toBe(0);
});

it('counts on every request once the cache is disabled', function (): void {
    Config::set('ads.count_cache_ttl', 0);

    Ad::factory()->count(3)->create();

    $this->getJson('/api/v1/ads')->assertOk()->assertJsonPath('meta.total', 3);

    Ad::factory()->create();

    $this->getJson('/api/v1/ads')->assertOk()->assertJsonPath('meta.total', 4);
});
