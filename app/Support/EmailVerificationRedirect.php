<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\EmailVerificationStatus;
use Illuminate\Support\Facades\Config;

/**
 * Builds the SPA URL an activation link lands on. Shared by the controller and
 * by the InvalidSignatureException handler, which must agree on the path.
 */
final readonly class EmailVerificationRedirect
{
    public function to(EmailVerificationStatus $status): string
    {
        $path = Config::string('auth.verification.redirect_path');

        return $path.'?status='.$status->value;
    }
}
