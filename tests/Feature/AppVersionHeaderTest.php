<?php

declare(strict_types=1);

it('adds the app version header to web responses', function (): void {
    config()->set('app.version', 'test-build-123');

    $this->get('/')
        ->assertOk()
        ->assertHeader('X-App-Version', 'test-build-123');
});

it('adds the app version header to api responses', function (): void {
    config()->set('app.version', 'test-build-456');

    $this->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertHeader('X-App-Version', 'test-build-456');
});
