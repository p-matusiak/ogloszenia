<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\User;
use App\Support\AdListingPredicate;
use Illuminate\Support\Facades\DB;

it('ignores soft-deleted ads even when status is still active', function (): void {
    $ad = Ad::factory()->create([
        'title' => 'Aktywne ale soft-usunięte',
        'status' => AdStatus::Active,
    ]);

    $ad->delete();

    expect($ad->fresh()->status)->toBe(AdStatus::Active)
        ->and($ad->fresh()->deleted_at)->not->toBeNull()
        ->and(Ad::query()->whereKey($ad->id)->exists())->toBeFalse();

    $this->getJson('/api/v1/ads')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('treats soft-deleted ads as gone from the public listing', function (): void {
    $ad = Ad::factory()->create(['title' => 'Do usunięcia']);
    $ad->delete();

    $this->getJson('/api/v1/ads')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('allows reusing an ad slug after soft delete', function (): void {
    $ad = Ad::factory()->create(['slug' => 'rower-miejski-warszawa']);
    $ad->delete();

    $replacement = Ad::factory()->create(['slug' => 'rower-miejski-warszawa']);

    expect($replacement->slug)->toBe('rower-miejski-warszawa');
});

it('allows reusing a seller slug after account soft delete', function (): void {
    $seller = User::factory()->create(['slug' => 'jan-kowalski']);
    $seller->delete();

    $replacement = User::factory()->create(['slug' => 'jan-kowalski']);

    expect($replacement->slug)->toBe('jan-kowalski');
});

it('defines partial unique slug indexes that ignore soft-deleted rows', function (): void {
    foreach (['ads' => 'ads_slug_unique', 'users' => 'users_slug_unique'] as $table => $name) {
        $indexes = collect(DB::select(
            'SELECT indexname, indexdef FROM pg_indexes WHERE tablename = ?',
            [$table],
        ))->keyBy('indexname');

        expect($indexes->has($name))->toBeTrue("Missing index {$name}");

        $definition = (string) $indexes->get($name)->indexdef;

        expect($definition)
            ->toContain('UNIQUE')
            ->toContain(AdListingPredicate::SOFT_DELETED_EXCLUDED);
    }

    expect(AdListingPredicate::PARTIAL_UNIQUE_INDEX_NAMES)->toBe([
        'ads_slug_unique',
        'users_slug_unique',
    ]);
});
