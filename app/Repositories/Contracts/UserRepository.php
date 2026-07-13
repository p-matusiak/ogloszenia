<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateAttributes(User $user, array $attributes): User;

    public function clearEmailVerification(User $user): void;
}
