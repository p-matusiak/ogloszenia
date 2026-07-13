<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\AdSlugHistory;
use App\Models\User;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function adEditPayload(Ad $ad, array $overrides = []): array
{
    return array_merge([
        'title' => $ad->title,
        'description' => $ad->description,
        'category_id' => $ad->category_id,
        'price' => 100,
        'location' => $ad->location,

        'accept_terms' => true,
    ], $overrides);
}

function adWithSlug(User $author, string $slug): Ad
{
    return Ad::factory()->for($author)->create([
        'title' => 'Rower gorski',
        'location' => 'Warszawa',
        'slug' => $slug,
    ]);
}

it('redirects the old url to the new one with 301 after a title change', function (): void {
    $author = User::factory()->create();
    $ad = adWithSlug($author, 'rower-gorski-warszawa');

    $this->actingAs($author)
        ->post('/api/v1/ads/'.$ad->slug, adEditPayload($ad, ['title' => 'Rower szosowy']))
        ->assertOk();

    $renamed = $ad->fresh();
    expect($renamed->slug)->not->toBe('rower-gorski-warszawa');

    $this->get('/ogloszenie/rower-gorski-warszawa')
        ->assertStatus(301)
        ->assertRedirect(route('ads.show', ['slug' => $renamed->slug]));
});

it('reclaims a slug from history when the author reverts the title', function (): void {
    $author = User::factory()->create();
    $ad = adWithSlug($author, 'rower-gorski-warszawa');

    $this->actingAs($author)
        ->post('/api/v1/ads/'.$ad->slug, adEditPayload($ad, ['title' => 'Rower szosowy']))
        ->assertOk();

    $renamed = $ad->fresh();

    $this->actingAs($author)
        ->post('/api/v1/ads/'.$renamed->slug, adEditPayload($renamed, ['title' => 'Rower gorski']))
        ->assertOk();

    // Wracamy pod stary adres, a jego wpis w historii nie może zostać sierotą
    // kolidującą z unikalnym indeksem.
    expect($ad->fresh()->slug)->toBe('rower-gorski-warszawa');
    expect(AdSlugHistory::query()->where('slug', 'rower-gorski-warszawa')->exists())->toBeFalse();
});

it('never hands a retired slug to a different ad', function (): void {
    $author = User::factory()->create();
    $retired = adWithSlug($author, 'rower-gorski-warszawa');

    $this->actingAs($author)
        ->post('/api/v1/ads/'.$retired->slug, adEditPayload($retired, ['title' => 'Rower szosowy']))
        ->assertOk();

    // Drugi autor wystawia identycznie nazwane ogłoszenie w tym samym mieście.
    $payload = validAdPayload(leafCategory(), [
        'title' => 'Rower gorski',
        'location' => 'Warszawa',
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', $payload)
        ->assertCreated();

    // Gdyby generator go oddał, stary zaindeksowany link zacząłby wskazywać
    // na zupełnie cudzą ofertę.
    expect($response->json('data.slug'))->not->toBe('rower-gorski-warszawa');
});

it('answers 404 when the retired slug belongs to no ad at all', function (): void {
    $this->get('/ogloszenie/nigdy-nie-bylo')->assertNotFound();
});
