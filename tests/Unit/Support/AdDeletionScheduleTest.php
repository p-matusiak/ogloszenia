<?php

declare(strict_types=1);

use App\Support\AdDeletionSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;

it('computes warning and deletion deadlines from expires_at', function (): void {
    Config::set('ads.refresh_grace_days', 30);
    Config::set('ads.deletion_warning_days', 5);

    $schedule = new AdDeletionSchedule;
    $expiresAt = CarbonImmutable::parse('2026-01-01 12:00:00');

    expect($schedule->scheduledDeletionAt($expiresAt)->toDateTimeString())
        ->toBe('2026-01-31 12:00:00')
        ->and($schedule->warningDueAt($expiresAt)->toDateTimeString())
        ->toBe('2026-01-26 12:00:00');
});

it('treats refresh as available only before the deletion deadline', function (): void {
    Config::set('ads.refresh_grace_days', 30);

    $schedule = new AdDeletionSchedule;
    $expiresAt = CarbonImmutable::parse('2026-01-01 12:00:00');

    $this->travelTo('2026-01-30 12:00:00');
    expect($schedule->isWithinRefreshGrace($expiresAt))->toBeTrue();

    $this->travelTo('2026-01-31 12:00:00');
    expect($schedule->isWithinRefreshGrace($expiresAt))->toBeFalse();
});
