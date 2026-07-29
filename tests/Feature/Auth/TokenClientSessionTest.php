<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Żądania klienta natywnego nie przechodzą przez middleware sesji, więc
 * kontroler nie może zakładać, że sesja w ogóle istnieje. Testy symulują to
 * przez wyłączenie middleware sesji dla pojedynczego żądania.
 */
it('registers an account without a session', function (): void {
    $this->withoutMiddleware(\Illuminate\Session\Middleware\StartSession::class)
        ->postJson('/api/v1/auth/register', [
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'password' => 'sekretne-haslo-123',
            'password_confirmation' => 'sekretne-haslo-123',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'jan@example.com');
});

it('signs in without a session', function (): void {
    User::factory()->create([
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
    ]);

    $this->withoutMiddleware(\Illuminate\Session\Middleware\StartSession::class)
        ->postJson('/api/v1/auth/login', [
            'email' => 'jan@example.com',
            'password' => 'sekretne-haslo-123',
        ])
        ->assertOk()
        ->assertJsonPath('data.email', 'jan@example.com');
});

it('signs out a token client without a session', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Pixel 8')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withoutMiddleware(\Illuminate\Session\Middleware\StartSession::class)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();
});

it('still regenerates the session for browser clients', function (): void {
    User::factory()->create([
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
    ]);

    // Ścieżka przeglądarkowa ma działać jak dotąd — regeneracja identyfikatora
    // sesji po zalogowaniu jest zabezpieczeniem przed session fixation.
    $this->startSession();
    $previous = session()->getId();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
    ])->assertOk();

    expect(session()->getId())->not->toBe($previous);
});
