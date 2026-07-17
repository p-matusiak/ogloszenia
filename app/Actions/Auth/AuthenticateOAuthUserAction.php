<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\OAuthProvider;
use App\Models\User;
use App\Repositories\Contracts\OAuthAccountRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
            $user = $this->users->markEmailAsVerified($existingAccount->user);

            return $this->storeAvatarOnFirstOAuthLogin($user, $provider, $providerUserId, $socialUser);
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

        $user = $this->users->markEmailAsVerified($user);
        $user = $this->storeAvatarOnFirstOAuthLogin($user, $provider, $providerUserId, $socialUser);

        return $this->oauthAccounts
            ->createForUser($user, $provider, $providerUserId)
            ->user;
    }

    private function storeAvatarOnFirstOAuthLogin(
        User $user,
        OAuthProvider $provider,
        string $providerUserId,
        SocialiteUser $socialUser,
    ): User {
        if ($user->avatar_path !== null) {
            return $user;
        }

        $avatarUrl = trim((string) $socialUser->getAvatar());

        if ($avatarUrl === '') {
            return $user;
        }

        $response = Http::timeout(10)->get($avatarUrl);

        if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
            return $user;
        }

        $extension = $this->detectAvatarExtension((string) $response->header('Content-Type'));
        $path = sprintf('avatars/oauth/%s-%s-%s.%s', $provider->value, $providerUserId, Str::uuid(), $extension);

        Storage::disk('public')->put($path, $response->body());

        return $this->users->updateAttributes($user, ['avatar_path' => $path]);
    }

    private function detectAvatarExtension(string $contentType): string
    {
        return match (strtolower(trim(explode(';', $contentType)[0]))) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }
}
