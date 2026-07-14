<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;

final readonly class SyncUserDefaultLocationAction
{
    public function __construct(private UserRepository $users) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): void
    {
        $location = $data['location'] ?? null;
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;

        if (! is_string($location) || trim($location) === '') {
            $this->users->syncDefaultLocation($user, null, null, null);

            return;
        }

        $this->users->syncDefaultLocation(
            $user,
            $location,
            is_numeric($latitude) ? (float) $latitude : null,
            is_numeric($longitude) ? (float) $longitude : null,
        );
    }
}
