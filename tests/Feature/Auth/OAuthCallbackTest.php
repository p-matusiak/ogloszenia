<?php

declare(strict_types=1);

use App\Enums\OAuthProvider;
use App\Models\OAuthAccount;
use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

beforeEach(function (): void {
    config()->set('services.google.client_id', 'test-google-id');
    config()->set('services.google.client_secret', 'test-google-secret');
    config()->set('services.facebook.client_id', 'test-facebook-id');
    config()->set('services.facebook.client_secret', 'test-facebook-secret');
});

it('logs in an existing user linked to a social account', function (): void {
    $user = User::factory()->create(['email' => 'linked@example.com']);
    OAuthAccount::query()->create([
        'user_id' => $user->id,
        'provider' => OAuthProvider::Google,
        'provider_user_id' => 'google-existing',
    ]);

    mockSocialiteUser('google', 'google-existing', 'linked@example.com', 'Linked User');

    $this->get('/auth/google/callback')
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('creates a user and oauth account on first social login', function (): void {
    mockSocialiteUser('google', 'google-new', 'new-oauth@example.com', 'Nowy OAuth');

    $this->get('/auth/google/callback')
        ->assertRedirect('/');

    $user = User::query()->where('email', 'new-oauth@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user?->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);

    expect(OAuthAccount::query()->where('user_id', $user?->id)->count())->toBe(1);
});

it('links a social account to an existing user with the same email', function (): void {
    $user = User::factory()->create(['email' => 'same@example.com']);

    mockSocialiteUser('facebook', 'fb-42', 'same@example.com', 'Jan Kowalski');

    $this->get('/auth/facebook/callback')
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);

    expect(OAuthAccount::query()
        ->where('user_id', $user->id)
        ->where('provider', OAuthProvider::Facebook->value)
        ->exists())->toBeTrue();
});

it('relinks stale oauth account from a soft-deleted user during the same login attempt', function (): void {
    $deletedUser = User::factory()->create(['email' => 'stale@example.com']);
    $activeUser = User::factory()->create(['email' => 'retry@example.com']);

    $deletedUser->forceFill(['email' => 'deleted-google@example.invalid'])->save();
    $deletedUser->delete();

    OAuthAccount::query()->create([
        'user_id' => $deletedUser->id,
        'provider' => OAuthProvider::Google,
        'provider_user_id' => 'google-stale',
    ]);

    mockSocialiteUser('google', 'google-stale', 'retry@example.com', 'Retry User');

    $this->get('/auth/google/callback')
        ->assertRedirect('/');

    expect(OAuthAccount::query()
        ->where('provider', OAuthProvider::Google->value)
        ->where('provider_user_id', 'google-stale')
        ->where('user_id', $activeUser->id)
        ->exists())->toBeTrue();

    $this->assertAuthenticatedAs($activeUser);
});

it('redirects to login when social provider fails', function (): void {
    $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
    $provider->shouldReceive('redirectUrl')->andReturnSelf();
    $provider->shouldReceive('user')->andThrow(new RuntimeException('Provider unavailable'));

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);

    $this->withSession(['oauth_redirect' => '/ogloszenie/test-slug'])
        ->get('/auth/google/callback')
        ->assertRedirectContains('/logowanie')
        ->assertRedirectContains('oauth_error=failed')
        ->assertRedirectContains('redirect=%2Fogloszenie%2Ftest-slug');

    $this->assertGuest();
});

it('redirects to login when oauth provider is not configured', function (): void {
    config()->set('services.google.client_id', null);
    config()->set('services.google.client_secret', null);

    $this->get('/auth/google/redirect')
        ->assertRedirectContains('/logowanie')
        ->assertRedirectContains('oauth_error=unconfigured');
});

it('lists configured oauth providers', function (): void {
    config()->set('services.google.client_id', 'google-client');
    config()->set('services.google.client_secret', 'google-secret');
    config()->set('services.facebook.client_id', null);
    config()->set('services.facebook.client_secret', null);

    $this->getJson('/api/v1/auth/oauth-providers')
        ->assertOk()
        ->assertJsonPath('providers', ['google']);
});

/**
 * @param  non-empty-string  $driver
 * @param  non-empty-string  $id
 * @param  non-empty-string  $email
 * @param  non-empty-string  $name
 */
function mockSocialiteUser(string $driver, string $id, string $email, string $name): void
{
    $socialUser = Mockery::mock(SocialiteUser::class);
    $socialUser->shouldReceive('getId')->andReturn($id);
    $socialUser->shouldReceive('getEmail')->andReturn($email);
    $socialUser->shouldReceive('getName')->andReturn($name);
    $socialUser->shouldReceive('getNickname')->andReturn(null);

    $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
    $provider->shouldReceive('redirectUrl')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($socialUser);

    Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
}
