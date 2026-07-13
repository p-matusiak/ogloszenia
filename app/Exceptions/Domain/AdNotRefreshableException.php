<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Carbon\CarbonInterface;

final class AdNotRefreshableException extends DomainException
{
    public function __construct(private readonly ?CarbonInterface $refreshableAt = null)
    {
        parent::__construct('This ad cannot be refreshed yet.');
    }

    public function errorCode(): string
    {
        return 'AD_NOT_REFRESHABLE';
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return ['refreshable_at' => $this->refreshableAt?->toIso8601String()];
    }
}
