<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Models\Ad;

it('expires active ads whose 30 days have run out', function (): void {
    $lapsed = Ad::factory()->lapsed()->create();
    $live = Ad::factory()->create();

    $this->artisan('ads:expire')->assertSuccessful();

    expect($lapsed->refresh()->status)->toBe(AdStatus::Expired)
        ->and($live->refresh()->status)->toBe(AdStatus::Active);
});

it('leaves ads awaiting moderation alone', function (): void {
    $pending = Ad::factory()->pending()->create();

    $this->artisan('ads:expire')->assertSuccessful();

    expect($pending->refresh()->status)->toBe(AdStatus::Pending);
});

it('hides a lapsed ad from the public listing before the sweep even runs', function (): void {
    // Miotła chodzi raz na godzinę, więc ogłoszenie potrafi mieć jeszcze status
    // `active` mimo minionej daty wygaśnięcia. `scopePublished` liczy oba pola,
    // inaczej wygasła oferta trafiłaby do listingu, sitemapy i kanału RSS.
    $lapsed = Ad::factory()->lapsed()->create();

    $this->getJson('/api/v1/ads')->assertJsonCount(0, 'data');

    $this->artisan('ads:expire')->assertSuccessful();

    expect($lapsed->refresh()->status)->toBe(AdStatus::Expired);
    $this->getJson('/api/v1/ads')->assertJsonCount(0, 'data');
});
