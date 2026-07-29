<?php

declare(strict_types=1);

use App\Models\User;

it('issues a token for valid credentials', function (): void {
    User::factory()->create([
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
    ]);

    $response = $this->postJson('/api/v1/auth/token', [
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
        'device_name' => 'Pixel 8',
    ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'jan@example.com')
        ->assertJsonStructure(['token', 'data' => ['id', 'name', 'email']]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

it('rejects invalid credentials without revealing the account exists', function (): void {
    User::factory()->create([
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
    ]);

    $this->postJson('/api/v1/auth/token', [
        'email' => 'jan@example.com',
        'password' => 'zle-haslo',
        'device_name' => 'Pixel 8',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->postJson('/api/v1/auth/token', [
        'email' => 'nieznany@example.com',
        'password' => 'sekretne-haslo-123',
        'device_name' => 'Pixel 8',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('requires a device name', function (): void {
    $this->postJson('/api/v1/auth/token', [
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
    ])->assertUnprocessable()->assertJsonValidationErrors('device_name');
});

it('authenticates a protected endpoint with the bearer token', function (): void {
    User::factory()->create([
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
    ]);

    $token = $this->postJson('/api/v1/auth/token', [
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
        'device_name' => 'Pixel 8',
    ])->json('token');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'jan@example.com');
});

it('replaces the previous token for the same device', function (): void {
    $user = User::factory()->create([
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
    ]);

    $credentials = [
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
        'device_name' => 'Pixel 8',
    ];

    $first = $this->postJson('/api/v1/auth/token', $credentials)->json('token');
    $this->postJson('/api/v1/auth/token', $credentials)->assertCreated();

    expect($user->tokens()->where('name', 'Pixel 8')->count())->toBe(1);

    // Stary token musi przestać działać, inaczej wylogowanie na zgubionym
    // telefonie nie odcina dostępu.
    $this->withHeader('Authorization', "Bearer {$first}")
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

it('keeps tokens of other devices when one signs out', function (): void {
    User::factory()->create([
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
    ]);

    $phone = $this->postJson('/api/v1/auth/token', [
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
        'device_name' => 'Pixel 8',
    ])->json('token');

    $tablet = $this->postJson('/api/v1/auth/token', [
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
        'device_name' => 'Galaxy Tab',
    ])->json('token');

    $this->withHeader('Authorization', "Bearer {$phone}")
        ->deleteJson('/api/v1/auth/token')
        ->assertNoContent();

    // Strażnik trzyma użytkownika rozwiązanego przy żądaniu DELETE; bez tego
    // kolejne żądanie w tym samym teście przeszłoby na pamięci podręcznej,
    // a nie na tokenie, który właśnie unieważniliśmy.
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$phone}")
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();

    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$tablet}")
        ->getJson('/api/v1/auth/me')
        ->assertOk();
});
