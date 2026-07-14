<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;

it('filters the public listing by seller slug', function (): void {
    $seller = User::factory()->create(['slug' => 'anna-demo']);
    $other = User::factory()->create(['slug' => 'ktos-inny']);

    Ad::factory()->for($seller)->create(['title' => 'Od sprzedawcy']);
    Ad::factory()->for($other)->create(['title' => 'Od kogoś innego']);

    $this->getJson('/api/v1/ads?seller=anna-demo')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Od sprzedawcy');
});

it('rejects an unknown seller slug', function (): void {
    $this->getJson('/api/v1/ads?seller=nieistniejacy-sprzedawca')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('seller');
});
