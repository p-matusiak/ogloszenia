<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Config;

/**
 * Terminy odświeżenia i trwałego usunięcia po wygaśnięciu ogłoszenia.
 */
final class AdDeletionSchedule
{
    public function refreshGraceDays(): int
    {
        return Config::integer('ads.refresh_grace_days');
    }

    public function deletionWarningDays(): int
    {
        return Config::integer('ads.deletion_warning_days');
    }

    public function scheduledDeletionAt(CarbonInterface $expiresAt): CarbonImmutable
    {
        return CarbonImmutable::parse($expiresAt)->addDays($this->refreshGraceDays());
    }

    public function warningDueAt(CarbonInterface $expiresAt): CarbonImmutable
    {
        return $this->scheduledDeletionAt($expiresAt)->subDays($this->deletionWarningDays());
    }

    public function isWithinRefreshGrace(CarbonInterface $expiresAt): bool
    {
        return $this->scheduledDeletionAt($expiresAt)->isFuture();
    }

    public function isDeletionWarningDue(CarbonInterface $expiresAt): bool
    {
        return ! $this->warningDueAt($expiresAt)->isFuture();
    }

    public function isDeletionDue(CarbonInterface $expiresAt): bool
    {
        return ! $this->scheduledDeletionAt($expiresAt)->isFuture();
    }
}
