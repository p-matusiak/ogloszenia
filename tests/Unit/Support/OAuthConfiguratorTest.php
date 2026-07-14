<?php

declare(strict_types=1);

use App\Enums\OAuthProvider;
use App\Support\OAuthConfigurator;
use Illuminate\Support\Facades\Config;

it('detects configured oauth providers', function (): void {
    Config::set('app.url', 'https://zunto.example');
    Config::set('services.google.client_id', 'google-id');
    Config::set('services.google.client_secret', 'google-secret');
    Config::set('services.facebook.client_id', '');
    Config::set('services.facebook.client_secret', '');

    $configurator = new OAuthConfigurator;

    expect($configurator->isConfigured(OAuthProvider::Google))->toBeTrue()
        ->and($configurator->isConfigured(OAuthProvider::Facebook))->toBeFalse()
        ->and($configurator->configuredProviders())->toBe([OAuthProvider::Google])
        ->and($configurator->callbackUrl(OAuthProvider::Google))
        ->toBe('https://zunto.example/auth/google/callback');
});
