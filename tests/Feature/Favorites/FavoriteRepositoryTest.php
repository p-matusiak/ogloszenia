<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;
use App\Repositories\Contracts\FavoriteRepository;

it('adds a favourite and reports it among active ids', function (): void {
    $repo = app(FavoriteRepository::class);
    $user = User::factory()->create();
    $ad = Ad::factory()->create();

    $repo->add($user, $ad);

    expect($repo->activeFavoriteIdsFor($user))->toBe([$ad->id]);
});

it('adding the same favourite twice is idempotent', function (): void {
    $repo = app(FavoriteRepository::class);
    $user = User::factory()->create();
    $ad = Ad::factory()->create();

    $repo->add($user, $ad);
    $repo->add($user, $ad);

    expect($user->favoriteAds()->count())->toBe(1);
});

it('removing a favourite is idempotent', function (): void {
    $repo = app(FavoriteRepository::class);
    $user = User::factory()->create();
    $ad = Ad::factory()->create();

    $repo->remove($user, $ad);

    expect($user->favoriteAds()->count())->toBe(0);
});

it('paginates only active favourites', function (): void {
    $repo = app(FavoriteRepository::class);
    $user = User::factory()->create();
    $active = Ad::factory()->create();
    $expired = Ad::factory()->expired()->create();
    $user->favoriteAds()->attach([$active->id, $expired->id]);

    $page = $repo->paginateActiveForUser($user);

    expect($page->total())->toBe(1)
        ->and($page->collect()->first()?->id)->toBe($active->id);
});

it('returns every user favouriting an ad', function (): void {
    $repo = app(FavoriteRepository::class);
    $ad = Ad::factory()->create();
    $first = User::factory()->create();
    $second = User::factory()->create();
    $first->favoriteAds()->attach($ad->id);
    $second->favoriteAds()->attach($ad->id);

    $ids = $repo->usersFavoriting($ad)->pluck('id')->sort()->values()->all();

    expect($ids)->toBe([$first->id, $second->id]);
});
