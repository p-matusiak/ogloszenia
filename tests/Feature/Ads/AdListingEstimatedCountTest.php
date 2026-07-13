<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\Category;
use Illuminate\Support\Facades\Config;

it('returns an exact total for result sets below the estimate threshold', function (): void {
    Ad::factory()->count(5)->create();

    $this->getJson('/api/v1/ads')
        ->assertOk()
        ->assertJsonPath('meta.total', 5);
});

it('falls back to the planner estimate above the threshold for an unindexed filter', function (): void {
    // Filtr delivery nie ma pokrywającego indeksu, więc powyżej progu wchodzi
    // ścieżka szacowania. Próg poniżej liczby wierszy wymusza ją bez seedowania
    // milionów ogłoszeń.
    Config::set('ads.count_estimate_threshold', 3);

    Ad::factory()->count(6)->create(['delivery_methods' => ['courier']]);

    $total = $this->getJson('/api/v1/ads?delivery=courier')->assertOk()->json('meta.total');

    expect($total)->toBeInt()->toBeGreaterThan(0);
});

it('counts an indexed filter exactly even above the threshold', function (): void {
    // Kategoria ma pokrywający indeks, więc mimo niskiego progu liczymy dokładnie
    // — estymator planisty myliłby się na poddrzewie o rzędy wielkości.
    Config::set('ads.count_estimate_threshold', 1);

    $category = Category::factory()->create(['slug' => 'elektronika']);
    Ad::factory()->count(4)->in($category)->create();

    $this->getJson('/api/v1/ads?category=elektronika')
        ->assertOk()
        ->assertJsonPath('meta.total', 4);
});

it('always counts exactly when estimation is disabled', function (): void {
    Config::set('ads.count_estimate_threshold', 0);

    Ad::factory()->count(4)->create();

    $this->getJson('/api/v1/ads')
        ->assertOk()
        ->assertJsonPath('meta.total', 4);
});

it('keeps the first page correct regardless of the estimated total', function (): void {
    Config::set('ads.count_estimate_threshold', 1);

    Ad::factory()->count(3)->create(['title' => 'Widoczne']);

    $this->getJson('/api/v1/ads')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});
