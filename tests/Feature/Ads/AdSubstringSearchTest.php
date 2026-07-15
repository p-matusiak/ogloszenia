<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\Category;
use App\Models\User;
use App\Support\AdListingPredicate;
use Illuminate\Support\Facades\DB;

/**
 * Szybkie zasianie wielu aktywnych ogłoszeń bez narzutu fabryki — plan zapytania
 * ma sens dopiero na zbiorze, na którym planer realnie waży dostęp do indeksu.
 */
function seedActiveAds(int $count): void
{
    $user = User::factory()->create();
    $category = Category::factory()->childOf(Category::factory()->create())->create();
    $now = now()->toDateTimeString();

    $rows = [];

    for ($i = 0; $i < $count; $i++) {
        $rows[] = [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Przedmiot numer '.$i,
            'slug' => 'perf-'.$i,
            'description' => 'Opis ogloszenia numer '.$i,
            'status' => 'active',
            'is_negotiable' => false,
            'delivery_methods' => '[]',
            'delivery_prices' => '{}',
            'terms_accepted_at' => $now,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    foreach (array_chunk($rows, 500) as $chunk) {
        Ad::query()->insert($chunk);
    }
}

it('matches a phrase in the middle of a word, not only whole words', function (): void {
    Ad::factory()->create(['title' => 'Kubek termiczny', 'description' => 'Stalowy.']);
    Ad::factory()->create(['title' => 'Szklanka do herbaty', 'description' => 'Zwykla.']);

    // "ermicz" siedzi w środku słowa "termiczny" — full-text tego nie łapał.
    $this->getJson('/api/v1/ads?q=ermicz')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Kubek termiczny');
});

it('requires every word of the query to appear somewhere', function (): void {
    Ad::factory()->create(['title' => 'Rower gorski Kross', 'description' => 'Aluminiowa rama.']);
    Ad::factory()->create(['title' => 'Rower miejski Romet', 'description' => 'Stalowa rama.']);

    $this->getJson('/api/v1/ads?q='.urlencode('rower kross'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Rower gorski Kross');
});

it('matches a substring that spans the title and its category-free description', function (): void {
    Ad::factory()->create(['title' => 'iPhone 13', 'description' => 'Kolor grafitowy, 128 GB.']);

    $this->getJson('/api/v1/ads?q=grafit')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('treats a LIKE wildcard inside a word as a literal character', function (): void {
    Ad::factory()->create(['title' => 'Laptop abcxyz', 'description' => 'Sprawny.']);

    // Jako wildcard "abc%xyz" pasowałoby do "abcxyz"; dosłownie wymaga znaku %,
    // którego w tytule nie ma, więc nie może być trafienia.
    $this->getJson('/api/v1/ads?q='.urlencode('abc%xyz'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('ignores stray operator punctuation around a real word', function (): void {
    Ad::factory()->create(['title' => 'Sprzedam rower', 'description' => 'Tanio.']);

    $this->getJson('/api/v1/ads?q='.urlencode('rower & | !'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Sprzedam rower');
});

it('serves substring search through the pg_trgm index instead of scanning the heap', function (): void {
    // Na kilkuset wierszach seq scan jest tańszy i planer słusznie go wybiera;
    // dopiero na tej skali koszt przeglądania sterty przewyższa indeks trigramowy,
    // a to jego wybór chcemy chronić przed regresją (np. rozjazdem wyrażenia).
    seedActiveAds(12_000);
    Ad::factory()->create(['title' => 'Kubek termiczny stalowy']);

    DB::statement('ANALYZE ads');

    $plan = collect(Ad::query()->published()->matching('ermicz')->toBase()->explain()->all())
        ->map(static fn (mixed $row): string => (string) ((array) $row)['QUERY PLAN'])
        ->implode("\n");

    expect(strtolower($plan))
        ->toContain(strtolower(AdListingPredicate::SEARCH_TEXT_TRGM_INDEX_NAME))
        ->not->toContain('seq scan on ads');
});
