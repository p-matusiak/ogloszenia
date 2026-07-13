<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;
use App\Notifications\VerifyEmailAddress;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/**
 * @param  array<string, mixed>  $overrides
 */
function verificationUrl(User $user, array $overrides = []): string
{
    return URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        array_merge(['id' => $user->id, 'hash' => sha1($user->email)], $overrides),
    );
}

it('sends an activation mail when an account is registered', function (): void {
    Notification::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
        'password_confirmation' => 'sekretne-haslo-123',
    ])->assertCreated()->assertJsonPath('data.is_email_verified', false);

    $user = User::query()->where('email', 'jan@example.com')->sole();

    Notification::assertSentTo($user, VerifyEmailAddress::class);
    expect($user->hasVerifiedEmail())->toBeFalse();
});

it('activates the account when a signed link is followed', function (): void {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();

    $this->get(verificationUrl($user))
        ->assertRedirect('/weryfikacja-email?status=verified');

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('reports an already verified account without touching the timestamp', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()->subDay()]);
    $verifiedAt = $user->email_verified_at;

    $this->get(verificationUrl($user))
        ->assertRedirect('/weryfikacja-email?status=already-verified');

    expect($user->refresh()->email_verified_at?->timestamp)->toBe($verifiedAt?->timestamp);
});

it('refuses a link whose hash belongs to another address', function (): void {
    $user = User::factory()->unverified()->create();

    $this->get(verificationUrl($user, ['hash' => sha1('ktos-inny@example.com')]))
        ->assertRedirect('/weryfikacja-email?status=invalid');

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('refuses a link pointing at an account that no longer exists', function (): void {
    $user = User::factory()->unverified()->create();
    $url = verificationUrl($user);
    $user->delete();

    $this->get($url)->assertRedirect('/weryfikacja-email?status=invalid');
});

it('sends an expired-link page instead of a raw 403 when the signature is stale', function (): void {
    $user = User::factory()->unverified()->create();

    $expired = URL::temporarySignedRoute(
        'verification.verify',
        now()->subMinute(),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->get($expired)->assertRedirect('/weryfikacja-email?status=expired');

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('refuses an unsigned link', function (): void {
    $user = User::factory()->unverified()->create();

    $this->get("/email/weryfikacja/{$user->id}/".sha1($user->email))
        ->assertRedirect('/weryfikacja-email?status=expired');

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the activation mail to an unverified user', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/auth/email/verification-notification')
        ->assertOk();

    Notification::assertSentTo($user, VerifyEmailAddress::class);
});

it('refuses to resend to an already verified user', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/auth/email/verification-notification')
        ->assertStatus(409)
        ->assertJsonPath('code', 'EMAIL_ALREADY_VERIFIED');

    Notification::assertNothingSent();
});

it('refuses to resend for a guest', function (): void {
    $this->postJson('/api/v1/auth/email/verification-notification')
        ->assertUnauthorized();
});

it('exposes the verification flag on the current user', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.is_email_verified', false);
});

it('stops an unverified user from publishing an ad', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->postJson('/api/v1/ads', validAdPayload(leafCategory()))
        ->assertForbidden()
        ->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');

    expect(Ad::query()->count())->toBe(0);
});

it('lets an unverified user delete their own ad', function (): void {
    $user = User::factory()->unverified()->create();
    $ad = Ad::factory()->for($user)->for(leafCategory())->create();

    // Bound by slug, not id: see Ad::getRouteKeyName().
    $this->actingAs($user)
        ->deleteJson("/api/v1/ads/{$ad->slug}")
        ->assertNoContent();
});
