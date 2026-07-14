<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\OAuthProvider;
use App\Models\OAuthAccount;
use App\Models\User;
use App\Repositories\Contracts\OAuthAccountRepository;

final class EloquentOAuthAccountRepository implements OAuthAccountRepository
{
    public function findByProviderUser(OAuthProvider $provider, string $providerUserId): ?OAuthAccount
    {
        return OAuthAccount::query()
            ->where('provider', $provider->value)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }

    public function createForUser(User $user, OAuthProvider $provider, string $providerUserId): OAuthAccount
    {
        $account = new OAuthAccount([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
        ]);
        $account->save();

        return $account->refresh();
    }
}
