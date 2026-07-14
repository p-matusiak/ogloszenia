<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\OAuthProvider;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;

/**
 * Jedno miejsce na konfigurację Socialite i listę dostępnych dostawców OAuth.
 */
final class OAuthConfigurator
{
    public function isConfigured(OAuthProvider $provider): bool
    {
        $settings = Config::array('services.'.$provider->value);

        return filled($settings['client_id'] ?? null)
            && filled($settings['client_secret'] ?? null);
    }

    /**
     * @return list<OAuthProvider>
     */
    public function configuredProviders(): array
    {
        return array_values(array_filter(
            OAuthProvider::cases(),
            fn (OAuthProvider $provider): bool => $this->isConfigured($provider),
        ));
    }

    public function callbackUrl(OAuthProvider $provider): string
    {
        return rtrim(Config::string('app.url'), '/').'/auth/'.$provider->value.'/callback';
    }

    public function driver(OAuthProvider $provider): Provider
    {
        return Socialite::driver($provider->value);
    }
}
