<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;
use App\Notifications\FavoritedAdChanged;
use Illuminate\Support\Facades\Notification;

it('emails favouriters when a favourited ad changes', function (): void {
    Notification::fake();

    $owner = User::factory()->create();
    $ad = Ad::factory()->for($owner)->create(['price' => 100]);
    $favoriter = User::factory()->create();
    $favoriter->favoriteAds()->attach($ad->id);

    $this->actingAs($owner)
        ->postJson("/api/v1/ads/{$ad->slug}", validAdPayload($ad->category, ['price' => 250]))
        ->assertOk();

    Notification::assertSentTo($favoriter, FavoritedAdChanged::class);
});

it('does not email when no notable field changes', function (): void {
    Notification::fake();

    $owner = User::factory()->create();
    $ad = Ad::factory()->for($owner)->create([
        'location' => 'Warszawa',
        'latitude' => 52.2297,
        'longitude' => 21.0122,
    ]);
    $favoriter = User::factory()->create();
    $favoriter->favoriteAds()->attach($ad->id);

    // Te same pola istotne dla obserwujących — zmienia się tylko negocjowalność.
    $payload = validAdPayload($ad->category, [
        'title' => $ad->title,
        'description' => $ad->description,
        'price' => $ad->price === null ? null : (float) $ad->price,
        'location' => $ad->location,
        'latitude' => $ad->latitude === null ? null : (float) $ad->latitude,
        'longitude' => $ad->longitude === null ? null : (float) $ad->longitude,
        'is_negotiable' => ! $ad->is_negotiable,
    ]);

    $this->actingAs($owner)
        ->postJson("/api/v1/ads/{$ad->slug}", $payload)
        ->assertOk();

    Notification::assertNothingSent();
});

it('does not email a user who has not favourited the ad', function (): void {
    Notification::fake();

    $owner = User::factory()->create();
    $ad = Ad::factory()->for($owner)->create(['price' => 100]);
    $stranger = User::factory()->create();

    $this->actingAs($owner)
        ->postJson("/api/v1/ads/{$ad->slug}", validAdPayload($ad->category, ['price' => 300]))
        ->assertOk();

    Notification::assertNotSentTo($stranger, FavoritedAdChanged::class);
});
