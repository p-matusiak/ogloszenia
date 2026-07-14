<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Services\Ads\AdModerationResult;
use App\Services\Ads\AdPublicationDecisionResolver;

it('publishes immediately when ai approves and auto approval is on', function (): void {
    $resolver = app(AdPublicationDecisionResolver::class);

    $attributes = $resolver->resolveForCreate(AdModerationResult::approved(), autoApprove: true);

    expect($attributes['status'])->toBe(AdStatus::Active)
        ->and($attributes['published_at'])->not->toBeNull();
});

it('holds an ad for moderation when ai approves but auto approval is off', function (): void {
    $resolver = app(AdPublicationDecisionResolver::class);

    $attributes = $resolver->resolveForCreate(AdModerationResult::approved(), autoApprove: false);

    expect($attributes['status'])->toBe(AdStatus::Pending)
        ->and($attributes['published_at'])->toBeNull();
});

it('rejects an ad when ai flags the content regardless of auto approval', function (): void {
    $resolver = app(AdPublicationDecisionResolver::class);

    $attributes = $resolver->resolveForCreate(
        AdModerationResult::rejected('Treść zawiera wulgaryzmy.'),
        autoApprove: true,
    );

    expect($attributes['status'])->toBe(AdStatus::Rejected)
        ->and($attributes['rejection_reason'])->toBe('Treść zawiera wulgaryzmy.');
});

it('falls back to auto approval when ai is unavailable', function (): void {
    $resolver = app(AdPublicationDecisionResolver::class);

    $attributes = $resolver->resolveForCreate(AdModerationResult::unavailable(), autoApprove: true);

    expect($attributes['status'])->toBe(AdStatus::Active);
});

it('keeps an active ad active when ai is unavailable during an edit', function (): void {
    $resolver = app(AdPublicationDecisionResolver::class);
    $ad = Ad::factory()->make(['status' => AdStatus::Active]);

    $attributes = $resolver->resolveForUpdate($ad, AdModerationResult::unavailable(), autoApprove: true);

    expect($attributes)->toBe([]);
});
