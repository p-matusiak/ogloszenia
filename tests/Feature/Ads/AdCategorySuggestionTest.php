<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Contracts\AdCategorySuggester;
use Tests\Fakes\FakeAdCategorySuggester;

it('returns a suggested leaf category for a title', function (): void {
    $leaf = leafCategory();

    app()->instance(AdCategorySuggester::class, new FakeAdCategorySuggester(
        available: true,
        categoryId: $leaf->id,
    ));

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads/suggest-category', ['title' => 'Sprzedam iPhone 13'])
        ->assertOk()
        ->assertJsonPath('data.available', true)
        ->assertJsonPath('data.category_id', $leaf->id);
});

it('reports ai as unavailable without changing the form flow', function (): void {
    app()->instance(AdCategorySuggester::class, new FakeAdCategorySuggester(available: false));

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads/suggest-category', ['title' => 'Sprzedam iPhone 13'])
        ->assertOk()
        ->assertJsonPath('data.available', false)
        ->assertJsonPath('data.category_id', null);
});

it('requires authentication to suggest a category', function (): void {
    $this->postJson('/api/v1/ads/suggest-category', ['title' => 'Sprzedam iPhone 13'])
        ->assertUnauthorized();
});
