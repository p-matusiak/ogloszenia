<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Support\SellerSlugGenerator;
use Illuminate\Support\Str;

final class EloquentUserRepository implements UserRepository
{
    public function __construct(private readonly SellerSlugGenerator $slugGenerator) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User
    {
        $name = (string) ($attributes['name'] ?? '');
        $emailVerifiedAt = $attributes['email_verified_at'] ?? null;
        unset($attributes['email_verified_at']);

        $user = new User($attributes);
        $user->forceFill([
            'slug' => $this->slugGenerator->generate($name),
            'email_verified_at' => $emailVerifiedAt,
        ]);
        $user->save();

        return $user->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateAttributes(User $user, array $attributes): User
    {
        $previousName = $user->name;

        $user->update($attributes);

        if (array_key_exists('name', $attributes) && (string) $attributes['name'] !== $previousName) {
            $this->syncSlugForNameChange($user->refresh(), $previousName);
        }

        return $user->refresh();
    }

    public function updatePassword(User $user, string $password): User
    {
        $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();

        return $user->refresh();
    }

    public function clearEmailVerification(User $user): void
    {
        $user->forceFill(['email_verified_at' => null])->save();
    }

    public function markEmailAsVerified(User $user): User
    {
        if ($user->email_verified_at !== null) {
            return $user;
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->refresh();
    }

    public function findPublicSellerBySlug(string $slug): ?User
    {
        return User::query()
            ->withCount('activeAds')
            ->where('slug', $slug)
            ->first();
    }

    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function findOrFailById(int $id): User
    {
        return User::query()->findOrFail($id);
    }

    public function softDeleteAccount(User $user): void
    {
        $user->forceFill([
            'email' => sprintf('deleted-%d-%d@deleted.invalid', $user->id, now()->timestamp),
        ])->save();

        $user->delete();
    }

    public function syncSlugForNameChange(User $user, string $previousName): void
    {
        if ($user->name === $previousName) {
            return;
        }

        $previousSlug = $user->slug;
        $nextSlug = $this->slugGenerator->generate($user->name, $user->id);

        if ($nextSlug === $previousSlug) {
            return;
        }

        $user->slugHistories()->where('slug', $nextSlug)->delete();
        $user->slugHistories()->create(['slug' => $previousSlug]);
        $user->forceFill(['slug' => $nextSlug])->save();
    }

    public function syncDefaultLocation(
        User $user,
        ?string $location,
        ?float $latitude,
        ?float $longitude,
    ): User {
        $user->forceFill([
            'default_location' => $location,
            'default_latitude' => $latitude,
            'default_longitude' => $longitude,
        ])->save();

        return $user->refresh();
    }
}
