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
    $ad = Ad::factory()->for($owner)->create();
    $favoriter = User::factory()->create();
    $favoriter->favoriteAds()->attach($ad->id);

    // Te same tytuł, opis i cena — zmienia się tylko nieistotna dzielnica.
    $payload = validAdPayload($ad->category, [
        'title' => $ad->title,
        'description' => $ad->description,
        'price' => (float) $ad->price,
        'district' => 'Zupełnie inna dzielnica',
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
