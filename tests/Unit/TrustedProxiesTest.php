<?php

declare(strict_types=1);

use Illuminate\Http\Middleware\TrustProxies;

it('splits the trusted proxy list and trims stray whitespace', function (): void {
    // phpunit.xml supplies "10.0.0.0/8, 172.16.0.0/12 ,192.168.0.0/16".
    expect(config('proxies.trusted'))->toBe([
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
    ]);
});

it('applies the trusted proxies to the middleware', function (): void {
    // Guards the wiring in AppServiceProvider: without it every visitor behind
    // the proxy shares one rate-limit bucket and HTTPS is never detected.
    $property = (new ReflectionClass(TrustProxies::class))->getProperty('alwaysTrustProxies');

    expect($property->getValue())->toBe(config('proxies.trusted'));
});
