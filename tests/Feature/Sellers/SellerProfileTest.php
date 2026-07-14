<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;

it('returns a public seller profile with an active ads count', function (): void {
    $seller = User::factory()->create([
        'name' => 'Anna Demo',
        'slug' => 'anna-demo',
        'bio' => 'Sprzedaję elektronikę.',
    ]);

    Ad::factory()->count(2)->for($seller)->create();
    Ad::factory()->for($seller)->pending()->create();

    $this->getJson('/api/v1/sellers/anna-demo')
        ->assertOk()
        ->assertJsonPath('data.id', $seller->id)
        ->assertJsonPath('data.slug', 'anna-demo')
        ->assertJsonPath('data.name', 'Anna Demo')
        ->assertJsonPath('data.bio', 'Sprzedaję elektronikę.')
        ->assertJsonPath('data.active_ads_count', 2);
});

it('returns 404 for a missing seller slug', function (): void {
    $this->getJson('/api/v1/sellers/nieistniejacy-sprzedawca')
        ->assertNotFound();
});

it('redirects a legacy numeric seller url to the slug canonical', function (): void {
    $seller = User::factory()->create(['slug' => 'jan-kowalski']);

    $this->get('/sprzedawca/'.$seller->id)
        ->assertRedirect('/sprzedawca/jan-kowalski');
});

it('redirects an old seller slug after a name change', function (): void {
    $seller = User::factory()->create([
        'name' => 'Stara Nazwa',
        'slug' => 'stara-nazwa',
    ]);

    $seller->slugHistories()->create(['slug' => 'poprzedni-slug']);

    $this->get('/sprzedawca/poprzedni-slug')
        ->assertRedirect('/sprzedawca/stara-nazwa');
});
