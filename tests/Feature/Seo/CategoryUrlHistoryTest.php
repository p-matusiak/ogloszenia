<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\CategorySlugHistory;
use App\Models\User;

function renameCategory(Category $category, string $name): void
{
    test()->actingAs(User::factory()->admin()->create())
        ->putJson('/api/v1/admin/categories/'.$category->slug, [
            'name' => $name,
            'parent_id' => $category->parent_id,
            'position' => $category->position,
            'is_visible' => $category->is_visible,
        ])
        ->assertOk();
}

it('redirects the old category url with 301 after a rename', function (): void {
    $category = Category::factory()->create(['slug' => 'motoryzacja', 'name' => 'Motoryzacja']);

    renameCategory($category, 'Auta i motocykle');

    $renamed = $category->fresh();
    expect($renamed->slug)->toBe('auta-i-motocykle');

    $this->get('/kategoria/motoryzacja')
        ->assertStatus(301)
        ->assertRedirect(route('categories.show', ['slug' => 'auta-i-motocykle']));
});

it('reclaims a slug from history when the admin reverts the name', function (): void {
    $category = Category::factory()->create(['slug' => 'motoryzacja', 'name' => 'Motoryzacja']);

    renameCategory($category, 'Auta');
    renameCategory($category->fresh(), 'Motoryzacja');

    expect($category->fresh()->slug)->toBe('motoryzacja');
    expect(CategorySlugHistory::query()->where('slug', 'motoryzacja')->exists())->toBeFalse();
});

it('never hands a retired category slug to a different branch of the tree', function (): void {
    $category = Category::factory()->create(['slug' => 'motoryzacja', 'name' => 'Motoryzacja']);

    renameCategory($category, 'Auta');

    $other = Category::factory()->create(['name' => 'Nieużywana']);
    renameCategory($other, 'Motoryzacja');

    // Gdyby generator oddał stary slug, `/kategoria/motoryzacja` przestałoby
    // przekierowywać i zaczęło pokazywać zupełnie inną gałąź.
    expect($other->fresh()->slug)->not->toBe('motoryzacja');
});

it('redirects the legacy query-string filters to the category page', function (): void {
    Category::factory()->create(['slug' => 'motoryzacja']);

    $this->get('/?category=motoryzacja')
        ->assertStatus(301)
        ->assertRedirect(route('categories.show', ['slug' => 'motoryzacja']));
});

it('keeps the remaining filters and prefers the deeper node when redirecting', function (): void {
    $root = Category::factory()->create(['slug' => 'motoryzacja']);
    Category::factory()->childOf($root)->create(['slug' => 'samochody']);

    $this->get('/?category=motoryzacja&subcategory=samochody&sort=price_asc')
        ->assertStatus(301)
        ->assertRedirect(route('categories.show', ['slug' => 'samochody']).'?sort=price_asc');
});

it('leaves an ordinary listing url alone', function (): void {
    $this->get('/?q=rower')->assertOk();
});
