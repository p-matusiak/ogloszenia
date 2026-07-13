<?php

declare(strict_types=1);

namespace App\Actions\Favorites;

use App\Models\Ad;
use App\Models\User;
use App\Repositories\Contracts\FavoriteRepository;

final readonly class RemoveFavoriteAction
{
    public function __construct(private FavoriteRepository $favorites) {}

    public function execute(User $user, Ad $ad): void
    {
        $this->favorites->remove($user, $ad);
    }
}
