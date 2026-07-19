<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;

it('returns the ad detail for a publicly visible ad', function (): void {
    Ad::factory()->create(['slug' => 'widoczne-ogloszenie']);

    $this->getJson('/api/v1/ads/widoczne-ogloszenie')
        ->assertOk()
        ->assertJsonPath('data.slug', 'widoczne-ogloszenie');
});

it('returns 410 for a deleted ad detail endpoint', function (): void {
    Ad::factory()->deleted()->create(['slug' => 'usuniete-ogloszenie']);

    $this->getJson('/api/v1/ads/usuniete-ogloszenie')->assertStatus(410);
});

it('returns 410 for the owner of a deleted ad too', function (): void {
    $owner = User::factory()->create();
    Ad::factory()->for($owner)->deleted()->create(['slug' => 'moje-usuniete-ogloszenie']);

    $this->actingAs($owner)
        ->getJson('/api/v1/ads/moje-usuniete-ogloszenie')
        ->assertStatus(410);
});

it('returns 404 for a pending ad when the visitor is not allowed to preview it', function (): void {
    Ad::factory()->pending()->create(['slug' => 'ukryte-ogloszenie']);

    $this->getJson('/api/v1/ads/ukryte-ogloszenie')->assertNotFound();
});

