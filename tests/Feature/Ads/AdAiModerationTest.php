<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Enums\SettingKey;
use App\Models\Ad;
use App\Models\User;
use App\Services\Contracts\AdContentModerator;
use App\Services\Contracts\SettingsRepository;
use Tests\Fakes\FakeAdContentModerator;

it('rejects an ad flagged by ai even when auto approval is on', function (): void {
    app()->instance(AdContentModerator::class, new FakeAdContentModerator(
        approved: false,
        rejectionReason: 'Treść zawiera wulgaryzmy.',
    ));
    app(SettingsRepository::class)->setEnabled(SettingKey::AutoApproveAds, true);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', validAdPayload(leafCategory()))
        ->assertCreated()
        ->assertJsonPath('data.status', AdStatus::Rejected->value)
        ->assertJsonPath('data.rejection_reason', 'Treść zawiera wulgaryzmy.');
});

it('publishes an ad when ai approves and auto approval is on', function (): void {
    app()->instance(AdContentModerator::class, new FakeAdContentModerator(approved: true));
    app(SettingsRepository::class)->setEnabled(SettingKey::AutoApproveAds, true);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', validAdPayload(leafCategory()))
        ->assertCreated()
        ->assertJsonPath('data.status', AdStatus::Active->value);
});

it('falls back to legacy auto approval when ai is unavailable', function (): void {
    app()->instance(AdContentModerator::class, new FakeAdContentModerator(available: false));
    app(SettingsRepository::class)->setEnabled(SettingKey::AutoApproveAds, true);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', validAdPayload(leafCategory()))
        ->assertCreated()
        ->assertJsonPath('data.status', AdStatus::Active->value);
});

it('falls back to pending moderation when ai is unavailable and auto approval is off', function (): void {
    app()->instance(AdContentModerator::class, new FakeAdContentModerator(available: false));
    app(SettingsRepository::class)->setEnabled(SettingKey::AutoApproveAds, false);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', validAdPayload(leafCategory()))
        ->assertCreated()
        ->assertJsonPath('data.status', AdStatus::Pending->value);
});

it('rejects an active ad on edit when ai flags updated content', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->for($user)->create();

    app()->instance(AdContentModerator::class, new FakeAdContentModerator(
        approved: false,
        rejectionReason: 'Treść zawiera niedozwolone materiały o charakterze seksualnym.',
    ));

    $this->actingAs($user)
        ->postJson("/api/v1/ads/{$ad->slug}", validAdPayload($ad->category, [
            'title' => 'Nowy tytuł ogłoszenia',
        ]))
        ->assertOk()
        ->assertJsonPath('data.status', AdStatus::Rejected->value);

    expect(Ad::query()->find($ad->id)?->status)->toBe(AdStatus::Rejected);
});
