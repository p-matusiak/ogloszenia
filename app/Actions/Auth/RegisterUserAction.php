<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;

final class RegisterUserAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): User
    {
        $user = User::query()->create($data);

        // Laravel listens for this event and dispatches the activation mail as
        // soon as the model implements MustVerifyEmail.
        event(new Registered($user));

        // Pull database defaults (is_admin) that the insert did not write back.
        return $user->refresh();
    }
}
