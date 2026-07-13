<?php

declare(strict_types=1);

use App\Models\Ad;

it('ranks ads that match more query terms higher', function (): void {
    Ad::factory()->create([
        'title' => 'Meble ogrodowe',
        'description' => 'Na tarasie stoi rower dzieciecy.',
    ]);
    Ad::factory()->create([
        'title' => 'Sprzedam rower gorski',
        'description' => 'Stan idealny.',
    ]);

    $this->getJson('/api/v1/ads?q=rower+gorski&sort=relevance')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Sprzedam rower gorski');
});

it('defaults to relevance ordering when a search term is present', function (): void {
    Ad::factory()->create(['title' => 'Pralka automatyczna', 'description' => 'Sprawna.']);
    Ad::factory()->create(['title' => 'Rower miejski', 'description' => 'Lekki rower na co dzien.']);

    $this->getJson('/api/v1/ads?q=rower')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Rower miejski');
});

it('matches search terms without Polish diacritics', function (): void {
    Ad::factory()->create(['title' => 'Łódź wodna dla dzieci', 'description' => 'Plastikowa.']);

    $this->getJson('/api/v1/ads?q=lodz')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('finds ads by location stored in the search index', function (): void {
    Ad::factory()->create([
        'title' => 'Biurko',
        'description' => 'Drewniane.',
        'location' => 'Bielsko-Biała',
    ]);
    Ad::factory()->create([
        'title' => 'Fotel',
        'description' => 'Wygodny.',
        'location' => 'Warszawa',
    ]);

    $this->getJson('/api/v1/ads?q=bielsko')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Biurko');
});

it('falls back to newest when relevance is requested without a query', function (): void {
    Ad::factory()->create(['title' => 'Starsze', 'published_at' => now()->subDays(3)]);
    Ad::factory()->create(['title' => 'Nowsze', 'published_at' => now()->subDay()]);

    $this->getJson('/api/v1/ads?sort=relevance')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Nowsze');
});
