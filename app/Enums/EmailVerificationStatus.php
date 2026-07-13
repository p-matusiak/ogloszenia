<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Outcome of following an activation link. Every case is a state the SPA has
 * to render, so the values double as the `?status=` query parameter.
 */
enum EmailVerificationStatus: string
{
    case Verified = 'verified';
    case AlreadyVerified = 'already-verified';

    /** The user id or the email hash in the link does not match any account. */
    case InvalidLink = 'invalid';

    /** The signature is missing, tampered with, or past its expiry. */
    case ExpiredLink = 'expired';

    public function isSuccessful(): bool
    {
        return $this === self::Verified || $this === self::AlreadyVerified;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
