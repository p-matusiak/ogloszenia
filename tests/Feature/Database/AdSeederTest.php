<?php

declare(strict_types=1);

use App\Models\Ad;
use Database\Seeders\AdSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Config;

it('seeds ads in batches with users and multiple real images', function (): void {
    Config::set([
        'seeding.ads_total' => 12,
        'seeding.ads_batch_size' => 5,
        'seeding.seller_count' => 3,
        'seeding.images_per_ad_min' => 2,
        'seeding.images_per_ad_max' => 3,
    ]);

    $this->seed(CategorySeeder::class);
    $this->seed(AdSeeder::class);

    $ads = Ad::query()
        ->where('slug', 'like', 'seed-ads-%')
        ->with(['user', 'images'])
        ->withCount('images')
        ->orderBy('id')
        ->get();

    expect($ads)->toHaveCount(12);
    expect($ads->every(fn (Ad $ad): bool => $ad->user !== null))->toBeTrue();
    expect($ads->every(fn (Ad $ad): bool => $ad->images_count >= 2 && $ad->images_count <= 3))->toBeTrue();

    $firstPaths = $ads->first()?->images->pluck('path')->all() ?? [];

    expect($firstPaths)->not->toBe([]);

    $this->seed(AdSeeder::class);

    expect(Ad::query()->where('slug', 'like', 'seed-ads-%')->count())->toBe(12);
});
