<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\EmailVerificationStatus;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

final class VerifyEmailAction
{
    public function execute(User $user, string $hash): EmailVerificationStatus
    {
        // The signature proves the link came from us; the hash proves it was
        // minted for this account's current address. Both are required.
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return EmailVerificationStatus::InvalidLink;
        }

        // Following the same link twice is a mail client prefetching it, not an
        // error worth showing the user.
        if ($user->hasVerifiedEmail()) {
            return EmailVerificationStatus::AlreadyVerified;
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return EmailVerificationStatus::Verified;
    }
}
