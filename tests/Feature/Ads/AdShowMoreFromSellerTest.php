<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;

it('lists other active ads from the same seller on a dedicated endpoint', function (): void {
    $seller = User::factory()->create();
    $current = Ad::factory()->for($seller)->create(['title' => 'Bieżące']);
    $related = Ad::factory()->for($seller)->create(['title' => 'Inne od sprzedawcy']);
    Ad::factory()->for($seller)->pending()->create(['title' => 'Oczekujące']);
    Ad::factory()->create(['title' => 'Cudze']);

    $response = $this->getJson('/api/v1/ads/'.$current->slug.'/more-from-seller')->assertOk();

    expect($response->json('data.*.title'))->toBe(['Inne od sprzedawcy']);
});

it('returns an empty list when the seller has no other active ads', function (): void {
    $ad = Ad::factory()->create();

    $this->getJson('/api/v1/ads/'.$ad->slug.'/more-from-seller')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('exposes the seller slug on the detail payload', function (): void {
    $seller = User::factory()->create([
        'name' => 'Jan Kowalski',
        'slug' => 'jan-kowalski',
    ]);
    $ad = Ad::factory()->for($seller)->create();

    $this->getJson('/api/v1/ads/'.$ad->slug)
        ->assertOk()
        ->assertJsonPath('data.seller.id', $seller->id)
        ->assertJsonPath('data.seller.slug', 'jan-kowalski')
        ->assertJsonPath('data.seller.name', 'Jan Kowalski');
});

it('does not load related ads in the detail payload', function (): void {
    $seller = User::factory()->create();
    $current = Ad::factory()->for($seller)->create();
    Ad::factory()->for($seller)->create();

    $this->getJson('/api/v1/ads/'.$current->slug)
        ->assertOk()
        ->assertJsonMissingPath('data.more_from_seller');
});
