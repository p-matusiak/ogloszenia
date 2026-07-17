<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('registers a user and signs them in', function (): void {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
        'password_confirmation' => 'sekretne-haslo-123',
    ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'jan@example.com')
        ->assertJsonPath('data.is_admin', false);

    $this->assertAuthenticated();
});

it('never returns the password hash', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonMissingPath('data.password');
});

it('refuses a duplicate email address', function (): void {
    User::factory()->create(['email' => 'jan@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
        'password_confirmation' => 'sekretne-haslo-123',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('signs an existing user in', function (): void {
    User::factory()->create(['email' => 'jan@example.com', 'password' => 'sekretne-haslo-123']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
    ])->assertOk();

    $this->assertAuthenticated();
});

it('gives the same error for a wrong password and an unknown address', function (): void {
    User::factory()->create(['email' => 'jan@example.com', 'password' => 'sekretne-haslo-123']);

    $wrongPassword = $this->postJson('/api/v1/auth/login', [
        'email' => 'jan@example.com',
        'password' => 'zle-haslo',
    ])->assertUnprocessable();

    $unknownEmail = $this->postJson('/api/v1/auth/login', [
        'email' => 'nikt@example.com',
        'password' => 'zle-haslo',
    ])->assertUnprocessable();

    // Identical wording, so the endpoint cannot be used to enumerate accounts.
    expect($wrongPassword->json('errors.email'))->toBe($unknownEmail->json('errors.email'));
});

it('signs a user out by tearing down the session', function (): void {
    // Act as the session guard, not Sanctum's request guard: the SPA is
    // authenticated by its session cookie, and that is what logout destroys.
    $this->actingAs(User::factory()->create(), 'web')
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    $this->assertGuest('web');
});

it('turns away an anonymous request for the current user', function (): void {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('emails a reset link without revealing whether the account exists', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jan@example.com']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'jan@example.com'])->assertOk();
    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nikt@example.com'])->assertOk();

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('resets the password and allows signing in with the new one', function (): void {
    $user = User::factory()->create([
        'email' => 'jan@example.com',
        'password' => 'stare-haslo-123',
    ]);

    $token = Password::broker()->createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'jan@example.com',
        'token' => $token,
        'password' => 'nowe-haslo-123',
        'password_confirmation' => 'nowe-haslo-123',
    ])->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'jan@example.com',
        'password' => 'nowe-haslo-123',
    ])->assertOk();

    $this->assertAuthenticated();
});
