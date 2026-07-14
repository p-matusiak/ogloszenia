<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\User;
use App\Support\AdListingPredicate;
use Illuminate\Support\Facades\DB;

function seedNonPublishedAds(): Ad
{
    $visible = Ad::factory()->create([
        'title' => 'Widoczne ogłoszenie',
        'latitude' => 52.2297,
        'longitude' => 21.0122,
        'location' => 'Warszawa',
    ]);

    $inactiveCoords = ['latitude' => 50.0647, 'longitude' => 19.9450, 'location' => 'Kraków'];

    Ad::factory()->pending()->create(['title' => 'Oczekujące', ...$inactiveCoords]);
    Ad::factory()->rejected()->create(['title' => 'Odrzucone', ...$inactiveCoords]);
    Ad::factory()->expired()->create(['title' => 'Wygasłe', ...$inactiveCoords]);
    Ad::factory()->deleted()->create(['title' => 'Usunięte', ...$inactiveCoords]);
    Ad::factory()->lapsed()->create(['title' => 'Przeterminowane', ...$inactiveCoords]);

    $softDeleted = Ad::factory()->create(['title' => 'Soft deleted', ...$inactiveCoords]);
    $softDeleted->forceFill(['status' => AdStatus::Deleted])->save();
    $softDeleted->delete();

    return $visible;
}

/**
 * Audyt: publiczne ścieżki odczytu zwracają wyłącznie ogłoszenia z scope published().
 */
it('excludes every non-published state from the public ads api', function (string $endpoint): void {
    $visible = seedNonPublishedAds();

    $this->getJson($endpoint)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', $visible->title);
})->with([
    'listing' => ['/api/v1/ads'],
    'search' => ['/api/v1/ads?q=Widoczne'],
    'geo filter' => ['/api/v1/ads?lat=52.2297&lng=21.0122&radius_km=50'],
]);

it('excludes non-published ads when filtering by seller slug', function (): void {
    $seller = User::factory()->create(['slug' => 'audit-seller']);

    $visible = Ad::factory()->for($seller)->create(['title' => 'Widoczne ogłoszenie']);
    Ad::factory()->for($seller)->pending()->create();
    Ad::factory()->for($seller)->lapsed()->create();
    Ad::factory()->for($seller)->expired()->create();

    $this->getJson('/api/v1/ads?seller=audit-seller')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', $visible->title);
});

it('lists only published favorites for the authenticated user', function (): void {
    $user = User::factory()->create();
    $visible = Ad::factory()->create(['title' => 'Ulubione widoczne']);

    $user->favoriteAds()->attach($visible->id);
    $user->favoriteAds()->attach(Ad::factory()->pending()->create()->id);
    $user->favoriteAds()->attach(Ad::factory()->lapsed()->create()->id);

    $this->actingAs($user)
        ->getJson('/api/v1/my/favorites')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', $visible->title);
});

it('returns only published related ads from the same seller', function (): void {
    $seller = User::factory()->create();
    $current = Ad::factory()->for($seller)->create(['title' => 'Bieżące']);
    Ad::factory()->for($seller)->create(['title' => 'Inne aktywne']);
    Ad::factory()->for($seller)->pending()->create(['title' => 'Oczekujące']);
    Ad::factory()->for($seller)->lapsed()->create(['title' => 'Przeterminowane']);

    $this->getJson('/api/v1/ads/'.$current->slug.'/more-from-seller')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Inne aktywne');
});

it('defines listing partial indexes on the published predicate', function (): void {
    $indexes = collect(DB::select(
        'SELECT indexname, indexdef FROM pg_indexes WHERE tablename = ?',
        ['ads'],
    ))->keyBy('indexname');

    foreach (AdListingPredicate::PARTIAL_INDEX_NAMES as $name) {
        expect($indexes->has($name))->toBeTrue("Missing index {$name}");

        $definition = (string) $indexes->get($name)->indexdef;

        expect($definition)
            ->toContain("'active'")
            ->toContain('deleted_at IS NULL');
    }
});

it('defines partial gin indexes on the published predicate', function (): void {
    $indexes = collect(DB::select(
        'SELECT indexname, indexdef FROM pg_indexes WHERE tablename = ?',
        ['ads'],
    ))->keyBy('indexname');

    foreach (AdListingPredicate::PARTIAL_GIN_INDEX_NAMES as $name) {
        expect($indexes->has($name))->toBeTrue("Missing index {$name}");

        $definition = (string) $indexes->get($name)->indexdef;

        expect($definition)
            ->toContain("'active'")
            ->toContain('deleted_at IS NULL');
    }
});

it('does not keep legacy full-table listing indexes', function (): void {
    $indexes = collect(DB::select(
        'SELECT indexname FROM pg_indexes WHERE tablename = ?',
        ['ads'],
    ))->pluck('indexname');

    foreach (AdListingPredicate::LEGACY_INDEX_NAMES as $name) {
        expect($indexes)->not->toContain($name);
    }
});

it('indexes author lookups without soft-deleted rows', function (): void {
    $indexes = collect(DB::select(
        'SELECT indexname, indexdef FROM pg_indexes WHERE tablename = ?',
        ['ads'],
    ))->keyBy('indexname');

    $name = AdListingPredicate::USER_CREATED_INDEX_NAME;

    expect($indexes->has($name))->toBeTrue();

    $definition = (string) $indexes->get($name)->indexdef;

    expect($definition)
        ->toContain('deleted_at IS NULL')
        ->not->toContain("status = 'active'");
});
