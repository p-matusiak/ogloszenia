<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;

final class EloquentUserRepository implements UserRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateAttributes(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh();
    }

    public function clearEmailVerification(User $user): void
    {
        $user->forceFill(['email_verified_at' => null])->save();
    }
}
