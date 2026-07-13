<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\VerifyEmailAddress;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('stores an optional phone number on the profile', function (): void {
    $user = User::factory()->create(['phone' => null]);

    $this->actingAs($user)
        ->postJson('/api/v1/auth/profile', [
            'name' => $user->name,
            'email' => $user->email,
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
            'email' => 'jan.nowak@example.com',
            'bio' => 'Lubię okazje i elektronikę.',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Jan Nowak')
        ->assertJsonPath('data.email', 'jan.nowak@example.com')
        ->assertJsonPath('data.bio', 'Lubię okazje i elektronikę.');

    expect($user->refresh()->name)->toBe('Jan Nowak')
        ->and($user->email)->toBe('jan.nowak@example.com')
        ->and($user->bio)->toBe('Lubię okazje i elektronikę.');
});

it('drops the verification and mails a new link when the address changes', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jan@example.com']);

    $this->actingAs($user)
        ->postJson('/api/v1/auth/profile', [
            'name' => $user->name,
            'email' => 'nowy@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('data.is_email_verified', false);

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmailAddress::class);
});

it('keeps the verification when the address is submitted unchanged', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jan@example.com']);

    $this->actingAs($user)
        ->postJson('/api/v1/auth/profile', [
            'name' => 'Jan Nowak',
            'email' => 'jan@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('data.is_email_verified', true);

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    Notification::assertNothingSent();
});

it('does not let an unverified account become verified by changing its address', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create(['email' => 'jan@example.com']);

    $this->actingAs($user)
        ->postJson('/api/v1/auth/profile', [
            'name' => $user->name,
            'email' => 'inny@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('data.is_email_verified', false);

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('stores an avatar on the public disk and returns its url', function (): void {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/api/v1/auth/profile', [
            'name' => $user->name,
            'email' => $user->email,
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
            'email' => $user->email,
            'remove_avatar' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.avatar_url', null);

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/test.jpg');
});
