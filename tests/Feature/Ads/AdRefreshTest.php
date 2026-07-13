<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\User;

it('refuses to refresh an ad that is still live', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson("/api/v1/ads/{$ad->slug}/refresh")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'AD_NOT_REFRESHABLE');
});

it('extends an ad by another 30 days once it has lapsed', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->for($user)->lapsed()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/ads/{$ad->slug}/refresh")
        ->assertOk()
        ->assertJsonPath('data.status', AdStatus::Active->value);

    $ad->refresh();
    expect($ad->expires_at?->isFuture())->toBeTrue()
        ->and($ad->published_at?->diffInDays($ad->expires_at))->toBe(30.0);
});

it('brings an expired ad back to life', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->for($user)->expired()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/ads/{$ad->slug}/refresh")
        ->assertOk();

    expect($ad->refresh()->status)->toBe(AdStatus::Active);
});

it('will not refresh a rejected ad', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->for($user)->rejected()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/ads/{$ad->slug}/refresh")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'AD_NOT_REFRESHABLE');
});

it('stops a stranger from refreshing someone else\'s ad', function (): void {
    $ad = Ad::factory()->lapsed()->create();

    $this->actingAs(User::factory()->create())
        ->postJson("/api/v1/ads/{$ad->slug}/refresh")
        ->assertForbidden();
});
