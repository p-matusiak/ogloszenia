<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Models\Ad;
use App\Models\AdReport;
use App\Models\User;

it('lets a guest report a suspicious ad', function (): void {
    $ad = Ad::factory()->create();

    $this->postJson("/api/v1/ads/{$ad->slug}/reports", ['reason' => 'spam'])
        ->assertAccepted();

    $report = AdReport::query()->sole();
    expect($report->reporter_id)->toBeNull()
        ->and($report->status)->toBe(ReportStatus::Pending);
});

it('remembers who reported when the reporter is signed in', function (): void {
    $ad = Ad::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/ads/{$ad->slug}/reports", ['reason' => 'scam', 'message' => 'Prosi o przedplate.'])
        ->assertAccepted();

    expect(AdReport::query()->sole()->reporter_id)->toBe($user->id);
});

it('rejects a reason it does not recognise', function (): void {
    $ad = Ad::factory()->create();

    $this->postJson("/api/v1/ads/{$ad->slug}/reports", ['reason' => 'nie-istnieje'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');
});

it('shows pending reports to an admin and lets them resolve one', function (): void {
    $report = AdReport::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson('/api/v1/admin/reports')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs(User::factory()->admin()->create())
        ->putJson("/api/v1/admin/reports/{$report->id}", ['status' => ReportStatus::Dismissed->value])
        ->assertOk();

    expect($report->refresh()->status)->toBe(ReportStatus::Dismissed);
});
