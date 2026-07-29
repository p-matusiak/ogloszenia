<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Cache;

it('exchanges a one-time oauth code for a bearer token', function (): void {
    $user = User::factory()->create(['email' => 'social@example.com']);
    $code = str_repeat('a', 64);
    Cache::put('mobile_oauth:'.$code, $user->id, now()->addMinutes(2));

    $response = $this->postJson('/api/v1/auth/oauth/mobile/exchange', [
        'code' => $code,
        'device_name' => 'Pixel 8',
    ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'social@example.com')
        ->assertJsonStructure(['token', 'data' => ['id', 'name', 'email']]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

it('allows an oauth code to be exchanged only once', function (): void {
    $user = User::factory()->create();
    $code = str_repeat('b', 64);
    Cache::put('mobile_oauth:'.$code, $user->id, now()->addMinutes(2));

    $payload = ['code' => $code, 'device_name' => 'Pixel 8'];

    $this->postJson('/api/v1/auth/oauth/mobile/exchange', $payload)->assertCreated();
    $this->postJson('/api/v1/auth/oauth/mobile/exchange', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('rejects an expired or unknown oauth code', function (): void {
    $this->postJson('/api/v1/auth/oauth/mobile/exchange', [
        'code' => str_repeat('c', 64),
        'device_name' => 'Pixel 8',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
});
