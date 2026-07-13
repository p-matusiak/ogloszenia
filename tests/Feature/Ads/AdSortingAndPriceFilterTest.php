<?php

declare(strict_types=1);

use App\Models\Ad;

it('sorts by newest publication by default', function (): void {
    Ad::factory()->create(['title' => 'Starsze', 'published_at' => now()->subDays(3)]);
    Ad::factory()->create(['title' => 'Nowsze', 'published_at' => now()->subHour()]);

    $this->getJson('/api/v1/ads')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Nowsze');
});

it('sorts from the cheapest and keeps priceless ads last', function (): void {
    Ad::factory()->create(['title' => 'Drogie', 'price' => 900]);
    Ad::factory()->create(['title' => 'Tanie', 'price' => 100]);
    Ad::factory()->create(['title' => 'Bez ceny', 'price' => null]);

    $response = $this->getJson('/api/v1/ads?sort=price_asc')->assertOk();

    // Postgres domyślnie daje NULLS LAST przy ASC, ale to musi być jawne.
    expect($response->json('data.*.title'))->toBe(['Tanie', 'Drogie', 'Bez ceny']);
});

it('sorts from the most expensive and still keeps priceless ads last', function (): void {
    Ad::factory()->create(['title' => 'Drogie', 'price' => 900]);
    Ad::factory()->create(['title' => 'Tanie', 'price' => 100]);
    Ad::factory()->create(['title' => 'Bez ceny', 'price' => null]);

    $response = $this->getJson('/api/v1/ads?sort=price_desc')->assertOk();

    // Bez NULLS LAST Postgres wypchnąłby „Bez ceny” na sam początek.
    expect($response->json('data.*.title'))->toBe(['Drogie', 'Tanie', 'Bez ceny']);
});

it('rejects an unknown sort key', function (): void {
    $this->getJson('/api/v1/ads?sort=losowo')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sort');
});

it('filters by a price range', function (): void {
    Ad::factory()->create(['title' => 'Za tanie', 'price' => 50]);
    Ad::factory()->create(['title' => 'W zakresie', 'price' => 500]);
    Ad::factory()->create(['title' => 'Za drogie', 'price' => 5000]);

    $this->getJson('/api/v1/ads?price_min=100&price_max=1000')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'W zakresie');
});

it('excludes ads without a price once a price range is given', function (): void {
    Ad::factory()->create(['title' => 'Bez ceny', 'price' => null]);

    $this->getJson('/api/v1/ads?price_min=100')->assertOk()->assertJsonCount(0, 'data');
});

it('refuses a maximum price below the minimum', function (): void {
    $this->getJson('/api/v1/ads?price_min=900&price_max=100')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('price_max');
});

it('filters by location, ignoring case and matching partially', function (): void {
    Ad::factory()->create(['title' => 'W stolicy', 'location' => 'Warszawa, Mokotów']);
    Ad::factory()->create(['title' => 'Nad morzem', 'location' => 'Gdańsk']);

    $this->getJson('/api/v1/ads?location=warszawa')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'W stolicy');
});

it('exposes the seller name and joining year, never their email', function (): void {
    $ad = Ad::factory()->create();
    $ad->user->update(['name' => 'Jan Kowalski']);

    $this->getJson("/api/v1/ads/{$ad->slug}")
        ->assertOk()
        ->assertJsonPath('data.seller.name', 'Jan Kowalski')
        ->assertJsonPath('data.seller.member_since', $ad->user->created_at?->year)
        ->assertJsonMissingPath('data.seller.email');
});
