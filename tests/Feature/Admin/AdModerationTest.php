<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Enums\SettingKey;
use App\Models\Ad;
use App\Models\User;
use App\Notifications\AdActivated;
use App\Services\Contracts\SettingsRepository;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
});

it('publishes a pending ad on approval', function (): void {
    Notification::fake();

    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller)->pending()->create();

    $this->actingAs($this->admin)
        ->postJson("/api/v1/admin/ads/{$ad->slug}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', AdStatus::Active->value);

    $ad->refresh();
    expect($ad->published_at)->not->toBeNull()
        ->and($ad->expires_at?->isFuture())->toBeTrue();

    $this->getJson('/api/v1/ads')->assertJsonCount(1, 'data');

    Notification::assertSentTo($seller, AdActivated::class);
});

it('records why an ad was rejected', function (): void {
    $ad = Ad::factory()->pending()->create();

    $this->actingAs($this->admin)
        ->postJson("/api/v1/admin/ads/{$ad->slug}/reject", ['reason' => 'Zdjecia naruszaja regulamin.'])
        ->assertOk()
        ->assertJsonPath('data.status', AdStatus::Rejected->value)
        ->assertJsonPath('data.rejection_reason', 'Zdjecia naruszaja regulamin.');

    $this->getJson('/api/v1/ads')->assertJsonCount(0, 'data');
});

it('demands a reason when rejecting', function (): void {
    $ad = Ad::factory()->pending()->create();

    $this->actingAs($this->admin)
        ->postJson("/api/v1/admin/ads/{$ad->slug}/reject", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');
});

it('sends a corrected rejected ad back for moderation when auto-approval is off', function (): void {
    app(SettingsRepository::class)->setEnabled(SettingKey::AutoApproveAds, false);

    $user = User::factory()->create();
    $ad = Ad::factory()->for($user)->rejected()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/ads/{$ad->slug}", validAdPayload($ad->category, ['title' => 'Poprawione ogloszenie']))
        ->assertOk()
        ->assertJsonPath('data.status', AdStatus::Pending->value)
        ->assertJsonPath('data.rejection_reason', null);
});

it('filters the admin list by status', function (): void {
    Ad::factory()->pending()->create();
    Ad::factory()->create();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/ads?status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', AdStatus::Pending->value);
});

it('bars a normal user from moderating', function (): void {
    $ad = Ad::factory()->pending()->create();

    $this->actingAs(User::factory()->create())
        ->postJson("/api/v1/admin/ads/{$ad->slug}/approve")
        ->assertForbidden();
});

it('bars a guest from moderating', function (): void {
    $ad = Ad::factory()->pending()->create();

    $this->postJson("/api/v1/admin/ads/{$ad->slug}/approve")->assertUnauthorized();
});
