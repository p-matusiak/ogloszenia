<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\User;
use App\Notifications\AdDeletionWarning;
use Illuminate\Support\Facades\Notification;

it('emails the owner five days before an expired ad is purged', function (): void {
    Notification::fake();

    $owner = User::factory()->create();
    $due = Ad::factory()->for($owner)->expiredDueForDeletionWarning()->create(['title' => 'Stary rower']);
    $tooEarly = Ad::factory()->for($owner)->expiredWithinRefreshGrace()->create();

    $this->artisan('ads:warn-deletion')->assertSuccessful();

    Notification::assertSentTo($owner, AdDeletionWarning::class);
    Notification::assertSentToTimes($owner, AdDeletionWarning::class, 1);

    expect($due->refresh()->deletion_warning_sent_at)->not->toBeNull()
        ->and($tooEarly->refresh()->deletion_warning_sent_at)->toBeNull();
});

it('does not resend a deletion warning once it was already sent', function (): void {
    Notification::fake();

    $owner = User::factory()->create();
    $ad = Ad::factory()->for($owner)->expiredDueForDeletionWarning()->create([
        'deletion_warning_sent_at' => now()->subDay(),
    ]);

    $this->artisan('ads:warn-deletion')->assertSuccessful();

    Notification::assertNothingSent();
    expect($ad->refresh()->deletion_warning_sent_at?->isToday())->toBeFalse();
});

it('purges expired ads that were not refreshed within the grace period', function (): void {
    $owner = User::factory()->create();
    $purge = Ad::factory()->for($owner)->expiredPastRefreshGrace()->create();
    $keep = Ad::factory()->for($owner)->expiredWithinRefreshGrace()->create();

    $this->artisan('ads:purge-expired')->assertSuccessful();

    expect($purge->refresh()->status)->toBe(AdStatus::Deleted)
        ->and($keep->refresh()->status)->toBe(AdStatus::Expired);
});

it('clears the deletion warning flag when an expired ad is refreshed', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->for($user)->expiredWithinRefreshGrace()->create([
        'deletion_warning_sent_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/ads/{$ad->slug}/refresh")
        ->assertOk();

    expect($ad->refresh())
        ->status->toBe(AdStatus::Active)
        ->deletion_warning_sent_at->toBeNull()
        ->expires_at?->isFuture()->toBeTrue();
});
