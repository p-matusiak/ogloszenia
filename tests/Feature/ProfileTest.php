<?php

declare(strict_types=1);

use App\Actions\Auth\RegisterUserAction;
use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('stores an optional phone number on the profile', function (): void {
    $user = User::factory()->create(['phone' => null]);

    $this->actingAs($user)
        ->postJson('/api/v1/auth/profile', [
            'name' => $user->name,
            'phone' => '+48 500 100 200',
        ])
        ->assertOk()
        ->assertJsonPath('data.phone', '+48 500 100 200');

    expect($user->refresh()->phone)->toBe('+48 500 100 200');
});

it('updates the basic profile data of the authenticated user', function (): void {
    $user = User::factory()->create([
        'name' => 'Jan',
        'email' => 'jan@example.com',
        'bio' => null,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/auth/profile', [
            'name' => 'Jan Nowak',
            'bio' => 'Lubię okazje i elektronikę.',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Jan Nowak')
        ->assertJsonPath('data.email', 'jan@example.com')
        ->assertJsonPath('data.bio', 'Lubię okazje i elektronikę.');

    expect($user->refresh()->name)->toBe('Jan Nowak')
        ->and($user->email)->toBe('jan@example.com')
        ->and($user->bio)->toBe('Lubię okazje i elektronikę.');
});

it('ignores attempts to change the email through the profile endpoint', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jan@example.com']);

    $this->actingAs($user)
        ->postJson('/api/v1/auth/profile', [
            'name' => $user->name,
            'email' => 'nowy@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('data.email', 'jan@example.com')
        ->assertJsonPath('data.is_email_verified', true);

    expect($user->refresh()->email)->toBe('jan@example.com');
    Notification::assertNothingSent();
});

it('stores an avatar on the public disk and returns its url', function (): void {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/api/v1/auth/profile', [
            'name' => $user->name,
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.avatar_url', fn (string $url): bool => str_contains($url, '/storage/avatars/'));

    expect($user->refresh()->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar_path);
});

it('removes the existing avatar when requested', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('avatars/test.jpg', 'avatar');

    $user = User::factory()->create(['avatar_path' => 'avatars/test.jpg']);

    $this->actingAs($user)
        ->postJson('/api/v1/auth/profile', [
            'name' => $user->name,
            'remove_avatar' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.avatar_url', null);

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/test.jpg');
});

it('soft deletes the account and all owned ads, then logs the user out', function (): void {
    $user = User::factory()->create(['email' => 'jan@example.com']);
    $ownedAd = Ad::factory()->for($user)->create(['status' => AdStatus::Active]);
    $otherAd = Ad::factory()->create(['status' => AdStatus::Active]);

    $this->actingAs($user, 'web')
        ->deleteJson('/api/v1/auth/account')
        ->assertNoContent();

    expect(User::query()->find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull()
        ->and(User::withTrashed()->find($user->id)?->email)->toStartWith('deleted-'.$user->id.'-')
        ->and(Ad::query()->find($ownedAd->id))->toBeNull()
        ->and(Ad::withTrashed()->find($ownedAd->id)?->status)->toBe(AdStatus::Deleted)
        ->and(Ad::query()->find($otherAd->id))->not->toBeNull();

    $this->assertGuest('web');
});

it('releases the email for a new account after deletion', function (): void {
    $user = User::factory()->create(['email' => 'jan@example.com']);

    $this->actingAs($user, 'web')
        ->deleteJson('/api/v1/auth/account')
        ->assertNoContent();

    expect(User::query()->where('email', 'jan@example.com')->exists())->toBeFalse();

    $replacement = app(RegisterUserAction::class)->execute([
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
    ]);

    expect($replacement->email)->toBe('jan@example.com');
});

it('rejects account deletion for guests', function (): void {
    $this->deleteJson('/api/v1/auth/account')->assertUnauthorized();
});
