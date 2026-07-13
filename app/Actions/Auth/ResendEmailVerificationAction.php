<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Exceptions\Domain\EmailAlreadyVerifiedException;
use App\Models\User;

final class ResendEmailVerificationAction
{
    /**
     * @throws EmailAlreadyVerifiedException
     */
    public function execute(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw new EmailAlreadyVerifiedException;
        }

        $user->sendEmailVerificationNotification();
    }
}
