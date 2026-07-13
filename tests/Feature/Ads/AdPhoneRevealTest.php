<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;

it('never puts the raw phone number in the public ad payload', function (): void {
    $ad = Ad::factory()->create(['contact_phone' => '+48 600 123 456']);

    $response = $this->getJson("/api/v1/ads/{$ad->slug}")->assertOk();

    // Cały payload, nie tylko pole telefonu: numer nie może wyciec nigdzie.
    expect($response->content())->not->toContain('123 456')
        ->and($response->content())->not->toContain('600123456');

    $response
        ->assertJsonPath('data.has_phone', true)
        ->assertJsonPath('data.contact_phone_masked', '+48 600 ••• •••')
        ->assertJsonMissingPath('data.contact_phone');
});

it('reports no phone when the seller left only an email', function (): void {
    $ad = Ad::factory()->create(['contact_phone' => null, 'contact_email' => 'jan@example.com']);

    $this->getJson("/api/v1/ads/{$ad->slug}")
        ->assertOk()
        ->assertJsonPath('data.has_phone', false)
        ->assertJsonPath('data.contact_phone_masked', null);
});

it('hands out the full number only on an explicit request', function (): void {
    $ad = Ad::factory()->create(['contact_phone' => '+48 600 123 456']);

    $this->postJson("/api/v1/ads/{$ad->slug}/phone")
        ->assertOk()
        ->assertJsonPath('phone', '+48 600 123 456');
});

it('counts every reveal', function (): void {
    $ad = Ad::factory()->create(['contact_phone' => '600123456']);

    $this->postJson("/api/v1/ads/{$ad->slug}/phone")->assertOk();
    $this->postJson("/api/v1/ads/{$ad->slug}/phone")->assertOk();

    expect($ad->refresh()->phone_reveals_count)->toBe(2);
});

it('answers 404 when the ad carries no phone number', function (): void {
    $ad = Ad::factory()->create(['contact_phone' => null, 'contact_email' => 'jan@example.com']);

    $this->postJson("/api/v1/ads/{$ad->slug}/phone")
        ->assertNotFound()
        ->assertJsonPath('code', 'AD_HAS_NO_PHONE');
});

it('refuses to reveal the phone of an ad that is not public', function (): void {
    $ad = Ad::factory()->pending()->create(['contact_phone' => '600123456']);

    $this->postJson("/api/v1/ads/{$ad->slug}/phone")->assertForbidden();
});

it('shows the owner their own full number, for the edit form', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->for($user)->create(['contact_phone' => '+48 600 123 456']);

    $this->actingAs($user)
        ->getJson("/api/v1/ads/{$ad->slug}")
        ->assertOk()
        ->assertJsonPath('data.contact_phone', '+48 600 123 456');
});

it('keeps a stranger from reading the raw number through the ad payload', function (): void {
    $ad = Ad::factory()->create(['contact_phone' => '+48 600 123 456']);

    $this->actingAs(User::factory()->create())
        ->getJson("/api/v1/ads/{$ad->slug}")
        ->assertOk()
        ->assertJsonMissingPath('data.contact_phone');
});

it('throttles bulk harvesting of numbers', function (): void {
    $ad = Ad::factory()->create(['contact_phone' => '600123456']);

    foreach (range(1, 15) as $ignored) {
        $this->postJson("/api/v1/ads/{$ad->slug}/phone")->assertOk();
    }

    $this->postJson("/api/v1/ads/{$ad->slug}/phone")->assertStatus(429);
});
