<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Auth\Events\Registered;

final class RegisterUserAction
{
    public function __construct(private readonly UserRepository $users) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): User
    {
        $user = $this->users->create($data);

        // Laravel listens for this event and dispatches the activation mail as
        // soon as the model implements MustVerifyEmail.
        event(new Registered($user));

        return $user;
    }
}
