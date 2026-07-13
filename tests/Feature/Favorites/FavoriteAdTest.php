<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;

it('adds an active ad to favourites', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/ads/{$ad->slug}/favorite")
        ->assertNoContent();

    expect($user->favoriteAds()->whereKey($ad->id)->exists())->toBeTrue();
});

it('rejects favouriting an inactive ad', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->expired()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/ads/{$ad->slug}/favorite")
        ->assertStatus(409)
        ->assertJsonPath('code', 'AD_NOT_FAVORITABLE');

    expect($user->favoriteAds()->count())->toBe(0);
});

it('requires authentication to favourite', function (): void {
    $ad = Ad::factory()->create();

    $this->postJson("/api/v1/ads/{$ad->slug}/favorite")->assertUnauthorized();
});

it('removes an ad from favourites', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->create();
    $user->favoriteAds()->attach($ad->id);

    $this->actingAs($user)
        ->deleteJson("/api/v1/ads/{$ad->slug}/favorite")
        ->assertNoContent();

    expect($user->favoriteAds()->whereKey($ad->id)->exists())->toBeFalse();
});

it('favouriting the same ad twice keeps a single entry', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->create();

    $this->actingAs($user)->postJson("/api/v1/ads/{$ad->slug}/favorite")->assertNoContent();
    $this->actingAs($user)->postJson("/api/v1/ads/{$ad->slug}/favorite")->assertNoContent();

    expect($user->favoriteAds()->count())->toBe(1);
});

it('lists only active favourites', function (): void {
    $user = User::factory()->create();
    $active = Ad::factory()->create();
    $expired = Ad::factory()->expired()->create();
    $user->favoriteAds()->attach([$active->id, $expired->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/my/favorites')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $active->slug);
});

it('returns active favourite ids', function (): void {
    $user = User::factory()->create();
    $active = Ad::factory()->create();
    $expired = Ad::factory()->expired()->create();
    $user->favoriteAds()->attach([$active->id, $expired->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/my/favorites/ids')
        ->assertOk()
        ->assertExactJson(['data' => [$active->id]]);
});
