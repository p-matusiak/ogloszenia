<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\Category;
use App\Models\User;
use App\Services\CategoryClosureRepository;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
});

it('gives a new root category a single self-referencing closure row', function (): void {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/categories', ['name' => 'Motoryzacja'])
        ->assertCreated();

    $category = Category::query()->sole();

    expect(closureRows($category->id))->toEqual([[$category->id, 0]]);
});

it('links a child to itself and to its parent', function (): void {
    $root = Category::factory()->create();

    $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/categories', [
            'name' => 'Samochody',
            'parent_id' => $root->id,
        ])
        ->assertCreated();

    $child = Category::query()->where('slug', 'samochody')->sole();

    expect(closureRows($child->id))->toEqual([
        [$child->id, 0],
        [$root->id, 1],
    ]);
});

it('re-links a whole subtree when a node is moved', function (): void {
    $oldRoot = Category::factory()->create();
    $newRoot = Category::factory()->create();
    $middle = Category::factory()->childOf($oldRoot)->create();
    $leaf = Category::factory()->childOf($middle)->create();

    expect($leaf->ancestors()->pluck('slug')->all())->toBe([$middle->slug, $oldRoot->slug]);

    $this->actingAs($this->admin)
        ->putJson("/api/v1/admin/categories/{$middle->slug}", [
            'name' => $middle->name,
            'parent_id' => $newRoot->id,
        ])
        ->assertOk();

    // The grandchild must follow its parent to the new root.
    expect($leaf->ancestors()->pluck('slug')->all())->toBe([$middle->slug, $newRoot->slug]);
});

it('refuses to move a category beneath its own descendant', function (): void {
    $root = Category::factory()->create();
    $child = Category::factory()->childOf($root)->create();

    $this->actingAs($this->admin)
        ->putJson("/api/v1/admin/categories/{$root->slug}", [
            'name' => $root->name,
            'parent_id' => $child->id,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'CATEGORY_INVALID_PARENT');
});

it('refuses to delete a category that still holds ads anywhere beneath it', function (): void {
    $root = Category::factory()->create();
    $leaf = Category::factory()->childOf($root)->create();
    Ad::factory()->in($leaf)->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/admin/categories/{$root->slug}")
        ->assertStatus(409)
        ->assertJsonPath('code', 'CATEGORY_IN_USE')
        ->assertJsonPath('details.ads_count', 1);
});

it('deletes an empty category together with its subtree', function (): void {
    $root = Category::factory()->create();
    Category::factory()->childOf($root)->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/admin/categories/{$root->slug}")
        ->assertNoContent();

    expect(Category::query()->count())->toBe(0)
        ->and(DB::table(CategoryClosureRepository::TABLE)->count())->toBe(0);
});

it('keeps hidden categories out of the public tree', function (): void {
    Category::factory()->create(['slug' => 'widoczna']);
    Category::factory()->hidden()->create(['slug' => 'ukryta']);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'widoczna');
});

it('generates a unique slug automatically from the category name', function (): void {
    Category::factory()->create(['name' => 'RTV', 'slug' => 'rtv']);

    $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/categories', ['name' => 'RTV'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'rtv-2');
});

it('returns the full nested category tree to the admin panel', function (): void {
    $root = Category::factory()->create(['name' => 'Elektronika', 'slug' => 'elektronika']);
    $child = Category::factory()->childOf($root)->create(['name' => 'Komputery', 'slug' => 'komputery']);
    Category::factory()->childOf($child)->create(['name' => 'Laptopy', 'slug' => 'laptopy']);

    $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/categories')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'elektronika')
        ->assertJsonPath('data.0.children.0.slug', 'komputery')
        ->assertJsonPath('data.0.children.0.children.0.slug', 'laptopy');
});

it('bars a non-admin from the category admin', function (): void {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/admin/categories', ['name' => 'X'])
        ->assertForbidden();
});

/**
 * @return list<array{int, int}> ancestor id and depth, nearest last
 */
function closureRows(int $nodeId): array
{
    return DB::table(CategoryClosureRepository::TABLE)
        ->where('descendant_id', $nodeId)
        ->orderBy('depth')
        ->get()
        ->map(fn (object $row): array => [(int) $row->ancestor_id, (int) $row->depth])
        ->all();
}
