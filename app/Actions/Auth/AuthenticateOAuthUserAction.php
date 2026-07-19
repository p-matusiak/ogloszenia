<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\OAuthProvider;
use App\Models\User;
use App\Repositories\Contracts\OAuthAccountRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class AuthenticateOAuthUserAction
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly OAuthAccountRepository $oauthAccounts,
    ) {}

    public function execute(OAuthProvider $provider, SocialiteUser $socialUser): User
    {
        $providerUserId = (string) $socialUser->getId();

        if ($providerUserId === '') {
            throw new UnprocessableEntityHttpException('Nie udało się odczytać identyfikatora konta społecznościowego.');
        }

        $existingAccount = $this->oauthAccounts->findByProviderUser($provider, $providerUserId);

        if ($existingAccount !== null) {
            $existingUser = $existingAccount->user;

            if ($existingUser instanceof User && ! $existingUser->trashed()) {
                return $existingUser;
            }

            $this->oauthAccounts->delete($existingAccount);
        }

        $email = $socialUser->getEmail();
        $name = trim((string) ($socialUser->getName() ?: $socialUser->getNickname()));

        if ($name === '') {
            $name = 'Użytkownik '.$provider->value;
        }

        $user = $email !== null
            ? $this->users->findByEmail($email)
            : null;

        if ($user === null) {
            if ($email === null) {
                throw new UnprocessableEntityHttpException('Dostawca nie przekazał adresu e-mail. Użyj logowania e-mailem.');
            }

            $user = $this->users->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::password(32)),
                'email_verified_at' => now(),
            ]);

            event(new Registered($user));
        }

        return $this->oauthAccounts
            ->createForUser($user, $provider, $providerUserId)
            ->user;
    }
}
