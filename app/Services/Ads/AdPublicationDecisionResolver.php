<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Support\AdPublicationWindow;

/**
 * Łączy wynik moderacji AI z ustawieniem auto-akceptacji. Gdy AI odrzuca treść,
 * ogłoszenie zawsze ląduje jako rejected — niezależnie od auto_approve_ads.
 */
final readonly class AdPublicationDecisionResolver
{
    public function __construct(private AdPublicationWindow $window) {}

    /**
     * @return array<string, mixed>
     */
    public function resolveForCreate(AdModerationResult $moderation, bool $autoApprove): array
    {
        if ($moderation->isRejected()) {
            return $this->rejected($moderation->rejectionReason);
        }

        if ($this->shouldAutoPublish($moderation, $autoApprove)) {
            return $this->active();
        }

        return $this->pending();
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveForUpdate(Ad $ad, AdModerationResult $moderation, bool $autoApprove): array
    {
        if ($moderation->isRejected()) {
            return $this->rejected($moderation->rejectionReason);
        }

        if ($ad->status === AdStatus::Rejected) {
            return $this->shouldAutoPublish($moderation, $autoApprove)
                ? $this->active()
                : $this->pending();
        }

        if ($ad->status === AdStatus::Pending && $this->shouldAutoPublish($moderation, $autoApprove)) {
            return $this->active();
        }

        return [];
    }

    private function shouldAutoPublish(AdModerationResult $moderation, bool $autoApprove): bool
    {
        return $autoApprove && (! $moderation->available || $moderation->approved === true);
    }

    /**
     * @return array<string, mixed>
     */
    private function rejected(?string $reason): array
    {
        return [
            'status' => AdStatus::Rejected,
            'rejection_reason' => $reason ?? 'Treść narusza regulamin serwisu.',
        ] + $this->window->closed();
    }

    /**
     * @return array<string, mixed>
     */
    private function active(): array
    {
        return [
            'status' => AdStatus::Active,
            'rejection_reason' => null,
        ] + $this->window->open();
    }

    /**
     * @return array<string, mixed>
     */
    private function pending(): array
    {
        return [
            'status' => AdStatus::Pending,
            'rejection_reason' => null,
        ] + $this->window->closed();
    }
}
