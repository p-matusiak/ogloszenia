<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Enums\SettingKey;
use App\Models\Ad;
use App\Models\Category;
use App\Models\User;
use App\Services\Contracts\SettingsRepository;

it('publishes an ad immediately when auto-approval is on', function (): void {
    app(SettingsRepository::class)->setEnabled(SettingKey::AutoApproveAds, true);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', validAdPayload(leafCategory()));

    $response->assertCreated()
        ->assertJsonPath('data.status', AdStatus::Active->value);

    $ad = Ad::query()->sole();
    expect($ad->published_at)->not->toBeNull()
        ->and($ad->published_at?->diffInDays($ad->expires_at))->toBe(30.0);
});

it('holds an ad for moderation when auto-approval is off', function (): void {
    app(SettingsRepository::class)->setEnabled(SettingKey::AutoApproveAds, false);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', validAdPayload(leafCategory()))
        ->assertCreated()
        ->assertJsonPath('data.status', AdStatus::Pending->value);

    $ad = Ad::query()->sole();
    expect($ad->published_at)->toBeNull()
        ->and($ad->expires_at)->toBeNull();
});

it('derives a readable slug from the title and location', function (): void {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', validAdPayload(leafCategory()))
        ->assertCreated()
        ->assertJsonPath('data.slug', 'sprzedam-iphone-13-128gb-warszawa');
});

it('refuses to file an ad directly under a top-level category', function (): void {
    $root = Category::factory()->create();
    Category::factory()->childOf($root)->create();

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', validAdPayload($root))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');
});

it('requires at least one way to contact the seller', function (): void {
    $payload = validAdPayload(leafCategory(), ['contact_email' => null, 'contact_phone' => null]);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['contact_email', 'contact_phone']);
});

it('requires the terms to be accepted', function (): void {
    $payload = validAdPayload(leafCategory(), ['accept_terms' => false]);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('accept_terms');
});

it('stops a user from flooding the site with ads in one day', function (): void {
    config()->set('ads.daily_limit_per_user', 2);

    $user = User::factory()->create();
    $leaf = leafCategory();

    foreach (range(1, 2) as $index) {
        $this->actingAs($user)
            ->postJson('/api/v1/ads', validAdPayload($leaf, ['title' => "Ogloszenie numer {$index}"]))
            ->assertCreated();
    }

    $this->actingAs($user)
        ->postJson('/api/v1/ads', validAdPayload($leaf, ['title' => 'Ogloszenie ponad limit']))
        ->assertStatus(429)
        ->assertJsonPath('code', 'ADS_DAILY_LIMIT_REACHED')
        ->assertJsonPath('details.limit', 2);
});

it('rejects an anonymous attempt to publish', function (): void {
    $this->postJson('/api/v1/ads', validAdPayload(leafCategory()))
        ->assertUnauthorized();
});
