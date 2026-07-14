<?php

declare(strict_types=1);

namespace App\Services\Ads;

final readonly class AdModerationResult
{
    private function __construct(
        public bool $available,
        public ?bool $approved,
        public ?string $rejectionReason,
    ) {}

    public static function unavailable(): self
    {
        return new self(available: false, approved: null, rejectionReason: null);
    }

    public static function approved(): self
    {
        return new self(available: true, approved: true, rejectionReason: null);
    }

    public static function rejected(string $reason): self
    {
        return new self(available: true, approved: false, rejectionReason: $reason);
    }

    public function isRejected(): bool
    {
        return $this->available && $this->approved === false;
    }
}
