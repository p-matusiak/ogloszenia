<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateAttributes(User $user, array $attributes): User;

    public function updatePassword(User $user, string $password): User;

    public function clearEmailVerification(User $user): void;

    public function markEmailAsVerified(User $user): User;

    public function findPublicSellerBySlug(string $slug): ?User;

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findOrFailById(int $id): User;

    public function softDeleteAccount(User $user): void;

    public function syncSlugForNameChange(User $user, string $previousName): void;

    public function syncDefaultLocation(
        User $user,
        ?string $location,
        ?float $latitude,
        ?float $longitude,
    ): User;
}
