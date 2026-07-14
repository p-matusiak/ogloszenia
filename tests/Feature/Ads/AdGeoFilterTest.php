<?php

declare(strict_types=1);

use App\Models\Ad;
use Illuminate\Support\Facades\DB;

it('returns ads within the requested radius', function (): void {
    Ad::factory()->create([
        'title' => 'W centrum Warszawy',
        'location' => 'Warszawa',
        'latitude' => 52.2297,
        'longitude' => 21.0122,
    ]);
    Ad::factory()->create([
        'title' => 'W Krakowie',
        'location' => 'Kraków',
        'latitude' => 50.0647,
        'longitude' => 19.9450,
    ]);

    $this->getJson('/api/v1/ads?lat=52.2297&lng=21.0122&radius_km=30')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'W centrum Warszawy');
});

it('excludes ads without coordinates from a geo filter', function (): void {
    Ad::factory()->create([
        'title' => 'Bez współrzędnych',
        'location' => 'Warszawa',
        'latitude' => null,
        'longitude' => null,
    ]);
    Ad::factory()->create([
        'title' => 'Z koordynatami',
        'location' => 'Warszawa',
        'latitude' => 52.2297,
        'longitude' => 21.0122,
    ]);

    $this->getJson('/api/v1/ads?lat=52.2297&lng=21.0122&radius_km=50')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Z koordynatami');
});

it('prefers geo over a textual location filter when coordinates are sent', function (): void {
    Ad::factory()->create([
        'title' => 'Warszawa geo',
        'location' => 'Warszawa',
        'latitude' => 52.2297,
        'longitude' => 21.0122,
    ]);
    Ad::factory()->create([
        'title' => 'Gdańsk tekst',
        'location' => 'Gdańsk',
        'latitude' => 54.3520,
        'longitude' => 18.6466,
    ]);

    $this->getJson('/api/v1/ads?location=gdańsk&lat=52.2297&lng=21.0122&radius_km=30')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Warszawa geo');
});

it('rejects a radius filter without every coordinate', function (): void {
    $this->getJson('/api/v1/ads?lat=52.2297&radius_km=25')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lng');
});

it('rejects an excessive search radius', function (): void {
    $this->getJson('/api/v1/ads?lat=52.2297&lng=21.0122&radius_km=9999')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('radius_km');
});

it('defines a partial GiST index on active ad coordinates', function (): void {
    $indexes = DB::select(
        "SELECT indexname FROM pg_indexes WHERE tablename = 'ads' AND indexname = 'ads_active_coordinates_gist'",
    );

    expect($indexes)->not->toBeEmpty();
});

it('applies ST_DWithin when filtering by radius', function (): void {
    Ad::factory()->create([
        'latitude' => 52.2297,
        'longitude' => 21.0122,
    ]);

    $rows = Ad::query()
        ->published()
        ->withinRadius(52.2297, 21.0122, 25)
        ->toBase()
        ->explain()
        ->all();

    $plan = strtolower(implode(' ', array_map(
        static fn (mixed $row): string => (string) ((array) $row)['QUERY PLAN'] ?? '',
        $rows,
    )));

    expect($plan)->toContain('st_dwithin');
});
