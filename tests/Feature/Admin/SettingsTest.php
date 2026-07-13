<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\User;
use App\Services\Contracts\SettingsRepository;

it('reports auto-approval as on by default', function (): void {
    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/api/v1/admin/settings')
        ->assertOk()
        ->assertJsonPath('auto_approve_ads', true);
});

it('lets an admin switch auto-approval off and back on', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->putJson('/api/v1/admin/settings', ['auto_approve_ads' => false])
        ->assertOk()
        ->assertJsonPath('auto_approve_ads', false);

    expect(app(SettingsRepository::class)->isEnabled(SettingKey::AutoApproveAds))->toBeFalse();

    $this->actingAs($admin)
        ->putJson('/api/v1/admin/settings', ['auto_approve_ads' => true])
        ->assertOk()
        ->assertJsonPath('auto_approve_ads', true);
});

it('bars a normal user from the settings', function (): void {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/v1/admin/settings')
        ->assertForbidden();
});
