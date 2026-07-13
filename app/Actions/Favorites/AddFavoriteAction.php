<?php

declare(strict_types=1);

namespace App\Actions\Favorites;

use App\Exceptions\Domain\AdNotFavoritableException;
use App\Models\Ad;
use App\Models\User;
use App\Repositories\Contracts\FavoriteRepository;

final readonly class AddFavoriteAction
{
    public function __construct(private FavoriteRepository $favorites) {}

    /**
     * Ulubione może być tylko aktywne, nieprzeterminowane ogłoszenie.
     *
     * @throws AdNotFavoritableException
     */
    public function execute(User $user, Ad $ad): void
    {
        if (! $ad->isPubliclyVisible()) {
            throw new AdNotFavoritableException;
        }

        $this->favorites->add($user, $ad);
    }
}
