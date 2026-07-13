<?php

declare(strict_types=1);

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    // Sanctum's statefulApi() middleware only attaches a session when the
    // request looks like it came from the first-party SPA, which a browser
    // signals with an Origin header. Without it the auth endpoints 500.
    //
    // withoutVite() belongs in the same closure: a second ->beforeEach() call
    // replaces this one rather than queueing after it, which would drop the
    // Origin header and take the session with it.
    ->beforeEach(fn () => test()
        ->withHeader('Origin', 'http://localhost')
        // The SPA shell is now rendered by tests asserting on its meta tags;
        // without this the suite would need `npm run build` to have run first.
        ->withoutVite())
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/**
 * Ads may only hang off leaf nodes, so most tests need a root plus one child.
 */
function leafCategory(?Category $root = null): Category
{
    return Category::factory()
        ->childOf($root ?? Category::factory()->create())
        ->create();
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validAdPayload(Category $leaf, array $overrides = []): array
{
    return array_merge([
        'title' => 'Sprzedam iPhone 13 128GB',
        'description' => 'Telefon w bardzo dobrym stanie, komplet dokumentow i pudelko.',
        'category_id' => $leaf->id,
        'price' => 1999.99,
        'location' => 'Warszawa',
        'contact_email' => 'sprzedawca@example.com',
        'accept_terms' => true,
    ], $overrides);
}
