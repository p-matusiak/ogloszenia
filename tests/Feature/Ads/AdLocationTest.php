<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;

it('stores coordinates with the location on create', function (): void {
    $user = User::factory()->create();

    $payload = validAdPayload(leafCategory(), [
        'location' => 'Warszawa, Mokotów',
        'latitude' => 52.204_009_3,
        'longitude' => 21.028_718_4,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/ads', $payload)
        ->assertCreated()
        ->assertJsonPath('data.location', 'Warszawa, Mokotów')
        ->assertJsonPath('data.latitude', 52.2040093)
        ->assertJsonPath('data.longitude', 21.0287184);

    $ad = Ad::query()->sole();

    expect($ad->location)->toBe('Warszawa, Mokotów')
        ->and((float) $ad->latitude)->toBe(52.2040093)
        ->and((float) $ad->longitude)->toBe(21.0287184);
});

it('syncs the first location to the user profile as default', function (): void {
    $user = User::factory()->create([
        'default_location' => null,
        'default_latitude' => null,
        'default_longitude' => null,
    ]);

    $payload = validAdPayload(leafCategory(), [
        'location' => 'Kraków',
        'latitude' => 50.064_650_1,
        'longitude' => 19.944_979_9,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/ads', $payload)
        ->assertCreated();

    $user->refresh();

    expect($user->default_location)->toBe('Kraków')
        ->and((float) $user->default_latitude)->toBe(50.0646501)
        ->and((float) $user->default_longitude)->toBe(19.9449799);
});

it('updates the default profile location when the ad location changes', function (): void {
    $user = User::factory()->create([
        'default_location' => 'Warszawa',
        'default_latitude' => 52.2297,
        'default_longitude' => 21.0122,
    ]);

    $ad = Ad::factory()->for($user)->create([
        'location' => 'Warszawa',
        'latitude' => 52.2297,
        'longitude' => 21.0122,
    ]);

    $payload = validAdPayload($ad->category, [
        'location' => 'Gdańsk',
        'latitude' => 54.352_025_2,
        'longitude' => 18.646_638_4,
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/ads/{$ad->slug}", $payload)
        ->assertOk()
        ->assertJsonPath('data.location', 'Gdańsk');

    $user->refresh();

    expect($user->default_location)->toBe('Gdańsk')
        ->and((float) $user->default_latitude)->toBe(54.3520252)
        ->and((float) $user->default_longitude)->toBe(18.6466384);
});

it('exposes default location on the authenticated user payload', function (): void {
    $user = User::factory()->create([
        'default_location' => 'Poznań',
        'default_latitude' => 52.406_374,
        'default_longitude' => 16.925_168_1,
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.default_location', 'Poznań')
        ->assertJsonPath('data.default_latitude', 52.406374)
        ->assertJsonPath('data.default_longitude', 16.9251681);
});

it('rejects a location without coordinates', function (): void {
    $payload = validAdPayload(leafCategory(), [
        'location' => 'Warszawa',
        'latitude' => null,
        'longitude' => null,
    ]);
    unset($payload['latitude'], $payload['longitude']);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});
