<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Services\Ads\AdModerationResult;
use App\Services\Contracts\AdContentModerator;

final class FakeAdContentModerator implements AdContentModerator
{
    public function __construct(
        private readonly bool $available = true,
        private readonly bool $approved = true,
        private readonly ?string $rejectionReason = null,
    ) {}

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function review(string $title, string $description): AdModerationResult
    {
        if (! $this->available) {
            return AdModerationResult::unavailable();
        }

        if ($this->approved) {
            return AdModerationResult::approved();
        }

        return AdModerationResult::rejected($this->rejectionReason ?? 'Treść odrzucona przez AI.');
    }
}
