<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\User;

it('hides a pending ad from the public', function (): void {
    $ad = Ad::factory()->pending()->create();

    $this->getJson("/api/v1/ads/{$ad->slug}")->assertForbidden();
});

it('lets the owner preview their own pending ad', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->for($user)->pending()->create();

    $this->actingAs($user)->getJson("/api/v1/ads/{$ad->slug}")->assertOk();
});

it('lets an admin see any ad', function (): void {
    $ad = Ad::factory()->pending()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/api/v1/ads/{$ad->slug}")
        ->assertOk();
});

it('stops a user from editing an ad they do not own', function (): void {
    $ad = Ad::factory()->create();

    $this->actingAs(User::factory()->create())
        ->postJson("/api/v1/ads/{$ad->slug}", validAdPayload($ad->category))
        ->assertForbidden();
});

it('stops a user from deleting an ad they do not own', function (): void {
    $ad = Ad::factory()->create();

    $this->actingAs(User::factory()->create())
        ->deleteJson("/api/v1/ads/{$ad->slug}")
        ->assertForbidden();
});

it('marks an ad deleted rather than erasing it', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->for($user)->create();

    $this->actingAs($user)->deleteJson("/api/v1/ads/{$ad->slug}")->assertNoContent();

    // The row survives so moderators can still inspect it.
    expect($ad->refresh()->status)->toBe(AdStatus::Deleted);
    $this->getJson('/api/v1/ads')->assertJsonCount(0, 'data');
});

it('counts a view when the ad is opened', function (): void {
    $ad = Ad::factory()->create(['views_count' => 0]);

    $this->getJson("/api/v1/ads/{$ad->slug}")->assertOk();

    expect($ad->refresh()->views_count)->toBe(1);
});

it('returns the freshly incremented view count, not the stale one', function (): void {
    $ad = Ad::factory()->create(['views_count' => 7]);

    $this->getJson("/api/v1/ads/{$ad->slug}")
        ->assertOk()
        ->assertJsonPath('data.views_count', 8);

    expect($ad->refresh()->views_count)->toBe(8);
});

it('answers 401 to an unauthenticated request that does not ask for JSON', function (): void {
    // A browser or crawler sends Accept: text/html. Laravel would otherwise try
    // to redirect to a `login` route this API-only app does not define.
    $this->get('/api/v1/admin/ads')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

it('answers 401 to an unauthenticated write that does not ask for JSON', function (): void {
    $this->post('/api/v1/ads')->assertUnauthorized();
});

it('only shows a user their own ads in the panel', function (): void {
    $user = User::factory()->create();
    Ad::factory()->for($user)->pending()->create(['title' => 'Moje ogloszenie']);
    Ad::factory()->create(['title' => 'Cudze ogloszenie']);

    $this->actingAs($user)
        ->getJson('/api/v1/my/ads')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Moje ogloszenie');
});
