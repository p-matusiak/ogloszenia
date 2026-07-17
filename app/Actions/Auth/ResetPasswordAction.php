<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Repositories\Contracts\UserRepository;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class ResetPasswordAction
{
    public function __construct(private readonly UserRepository $users) {}

    /**
     * @param  array{email: string, password: string, password_confirmation: string, token: string}  $data
     */
    public function execute(array $data): void
    {
        $status = Password::reset(
            $data,
            function ($user, string $password): void {
                $updatedUser = $this->users->updatePassword($user, $password);

                event(new PasswordReset($updatedUser));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }
    }
}
