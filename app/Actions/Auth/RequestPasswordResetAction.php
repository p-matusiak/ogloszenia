<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Password;

final class RequestPasswordResetAction
{
    public function execute(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }
}
