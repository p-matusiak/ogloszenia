<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\OAuthProvider;
use App\Models\OAuthAccount;
use App\Models\User;

interface OAuthAccountRepository
{
    public function findByProviderUser(OAuthProvider $provider, string $providerUserId): ?OAuthAccount;

    public function createForUser(User $user, OAuthProvider $provider, string $providerUserId): OAuthAccount;

    public function delete(OAuthAccount $account): void;
}
