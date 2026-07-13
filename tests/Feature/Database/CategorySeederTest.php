<?php

declare(strict_types=1);

use App\Models\Category;
use App\Services\CategoryClosureRepository;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\DB;

it('seeds a full category tree with stable hierarchy and no duplicates', function (): void {
    $this->seed(CategorySeeder::class);

    expect(Category::query()->roots()->orderBy('position')->pluck('slug')->all())->toEqual([
        'motoryzacja',
        'nieruchomosci',
        'elektronika',
        'dom-i-ogrod',
        'moda',
        'dla-dzieci',
        'sport-i-hobby',
        'praca',
        'uslugi',
    ]);

    expect(Category::query()->where('slug', 'laptopy')->sole()->ancestors()->pluck('slug')->all())
        ->toBe(['komputery', 'elektronika']);

    expect(Category::query()->where('slug', 'opony-i-felgi')->sole()->ancestors()->pluck('slug')->all())
        ->toBe(['czesci-i-akcesoria', 'motoryzacja']);

    $categoryCount = Category::query()->count();
    $closureCount = DB::table(CategoryClosureRepository::TABLE)->count();

    $this->seed(CategorySeeder::class);

    expect(Category::query()->count())->toBe($categoryCount)
        ->and(DB::table(CategoryClosureRepository::TABLE)->count())->toBe($closureCount);
});
