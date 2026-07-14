<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\SellerSlugGenerator;
use Illuminate\Support\Str;

it('generates a slug from the seller name', function (): void {
    $generator = app(SellerSlugGenerator::class);

    expect($generator->generate('TechPoint — Anna Kowalska'))->toBe('techpoint-anna-kowalska');
});

it('appends a numeric suffix when the base slug is already taken', function (): void {
    $token = Str::lower(Str::random(8));
    $name = "Suffix Kolizja {$token}";
    $baseSlug = Str::slug($name);

    $seller = User::factory()->create(['name' => $name]);
    $seller->forceFill(['slug' => $baseSlug])->saveQuietly();

    $generator = app(SellerSlugGenerator::class);

    expect($generator->generate($name))->toBe("{$baseSlug}-2");
});

it('treats historical slugs as taken', function (): void {
    $token = Str::lower(Str::random(8));
    $oldSlug = "poprzednia-nazwa-{$token}";
    $name = "Aktualna Nazwa {$token}";

    $seller = User::factory()->create(['name' => $name]);
    $seller->slugHistories()->create(['slug' => $oldSlug]);

    $generator = app(SellerSlugGenerator::class);

    expect($generator->generate("Poprzednia Nazwa {$token}"))->toBe("{$oldSlug}-2");
});

it('ignores the current user when regenerating their slug', function (): void {
    $token = Str::lower(Str::random(8));
    $name = "Unikalny Jan {$token}";
    $slug = Str::slug($name);

    $seller = User::factory()->create(['name' => $name]);
    $seller->forceFill(['slug' => $slug])->saveQuietly();

    $generator = app(SellerSlugGenerator::class);

    expect($generator->generate($name, $seller->id))->toBe($slug);
});
