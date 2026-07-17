<?php

declare(strict_types=1);

use App\Enums\OAuthProvider;
use App\Models\OAuthAccount;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

beforeEach(function (): void {
    config()->set('services.google.client_id', 'test-google-id');
    config()->set('services.google.client_secret', 'test-google-secret');
    config()->set('services.facebook.client_id', 'test-facebook-id');
    config()->set('services.facebook.client_secret', 'test-facebook-secret');
});

it('logs in an existing user linked to a social account', function (): void {
    $user = User::factory()->create([
        'email' => 'linked@example.com',
        'email_verified_at' => null,
    ]);

    OAuthAccount::query()->create([
        'user_id' => $user->id,
        'provider' => OAuthProvider::Google,
        'provider_user_id' => 'google-existing',
    ]);

    mockSocialiteUser('google', 'google-existing', 'linked@example.com', 'Linked User');

    $this->get('/auth/google/callback')
        ->assertRedirect('/');

    expect($user->refresh()->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user->refresh());
});

it('creates a user and oauth account on first social login', function (): void {
    Storage::fake('public');
    Http::fake([
        'https://cdn.example.com/avatar.jpg' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    mockSocialiteUser(
        'google',
        'google-new',
        'new-oauth@example.com',
        'Nowy OAuth',
        'https://cdn.example.com/avatar.jpg',
    );

    $this->get('/auth/google/callback')
        ->assertRedirect('/');

    $user = User::query()->where('email', 'new-oauth@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user?->email_verified_at)->not->toBeNull()
        ->and($user?->avatar_path)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
    Storage::disk('public')->assertExists($user->avatar_path);

    expect(OAuthAccount::query()->where('user_id', $user?->id)->count())->toBe(1);
});

it('links a social account to an existing user with the same email', function (): void {
    Storage::fake('public');
    Http::fake([
        'https://cdn.example.com/facebook-avatar.jpg' => Http::response('facebook-image', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $user = User::factory()->create([
        'email' => 'same@example.com',
        'email_verified_at' => null,
        'avatar_path' => null,
    ]);

    mockSocialiteUser(
        'facebook',
        'fb-42',
        'same@example.com',
        'Jan Kowalski',
        'https://cdn.example.com/facebook-avatar.jpg',
    );

    $this->get('/auth/facebook/callback')
        ->assertRedirect('/');

    expect($user->refresh()->email_verified_at)->not->toBeNull()
        ->and($user->refresh()->avatar_path)->not->toBeNull();
    $this->assertAuthenticatedAs($user->refresh());
    Storage::disk('public')->assertExists($user->refresh()->avatar_path);

    expect(OAuthAccount::query()
        ->where('user_id', $user->id)
        ->where('provider', OAuthProvider::Facebook->value)
        ->exists())->toBeTrue();
});

it('does not replace an existing local avatar on repeated social login', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/existing.jpg', 'existing-avatar');
    Http::fake([
        'https://cdn.example.com/new-avatar.jpg' => Http::response('new-image', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $user = User::factory()->create([
        'email' => 'avatar-linked@example.com',
        'avatar_path' => 'avatars/existing.jpg',
        'email_verified_at' => null,
    ]);

    OAuthAccount::query()->create([
        'user_id' => $user->id,
        'provider' => OAuthProvider::Google,
        'provider_user_id' => 'google-avatar-existing',
    ]);

    mockSocialiteUser(
        'google',
        'google-avatar-existing',
        'avatar-linked@example.com',
        'Avatar User',
        'https://cdn.example.com/new-avatar.jpg',
    );

    $this->get('/auth/google/callback')
        ->assertRedirect('/');

    expect($user->refresh()->avatar_path)->toBe('avatars/existing.jpg');
    Storage::disk('public')->assertExists('avatars/existing.jpg');
    Http::assertNothingSent();
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
function mockSocialiteUser(string $driver, string $id, string $email, string $name, ?string $avatar = null): void
{
    $socialUser = Mockery::mock(SocialiteUser::class);
    $socialUser->shouldReceive('getId')->andReturn($id);
    $socialUser->shouldReceive('getEmail')->andReturn($email);
    $socialUser->shouldReceive('getName')->andReturn($name);
    $socialUser->shouldReceive('getNickname')->andReturn(null);
    $socialUser->shouldReceive('getAvatar')->andReturn($avatar);

    $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
    $provider->shouldReceive('redirectUrl')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($socialUser);

    Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
}
