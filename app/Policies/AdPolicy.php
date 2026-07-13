<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\User;

final class AdPolicy
{
    /**
     * Admins moderate every ad, so they bypass the per-ad checks below.
     */
    public function before(User $user): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function view(?User $user, Ad $ad): bool
    {
        if ($ad->isPubliclyVisible()) {
            return true;
        }

        return $user !== null && $this->owns($user, $ad);
    }

    public function update(User $user, Ad $ad): bool
    {
        return $this->owns($user, $ad) && $ad->status !== AdStatus::Deleted;
    }

    public function delete(User $user, Ad $ad): bool
    {
        return $this->owns($user, $ad) && $ad->status !== AdStatus::Deleted;
    }

    public function refresh(User $user, Ad $ad): bool
    {
        return $this->owns($user, $ad);
    }

    private function owns(User $user, Ad $ad): bool
    {
        return $ad->user_id === $user->id;
    }
}
