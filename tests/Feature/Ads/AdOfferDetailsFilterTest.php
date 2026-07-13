<?php

declare(strict_types=1);

use App\Enums\AdCondition;
use App\Enums\DeliveryMethod;
use App\Models\Ad;
use App\Models\User;

it('filters ads open to negotiation', function (): void {
    Ad::factory()->create(['title' => 'Do negocjacji', 'is_negotiable' => true]);
    Ad::factory()->create(['title' => 'Cena stala', 'is_negotiable' => false]);

    $this->getJson('/api/v1/ads?negotiable=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Do negocjacji')
        ->assertJsonPath('data.0.is_negotiable', true);
});

it('treats "za darmo" as a price of zero, not as a missing price', function (): void {
    Ad::factory()->create(['title' => 'Za darmo', 'price' => 0]);
    Ad::factory()->create(['title' => 'Bez ceny', 'price' => null]);
    Ad::factory()->create(['title' => 'Platne', 'price' => 100]);

    $this->getJson('/api/v1/ads?free=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Za darmo');
});

it('filters by any of the selected conditions', function (): void {
    Ad::factory()->create(['title' => 'Nowe', 'condition' => AdCondition::New]);
    Ad::factory()->create(['title' => 'Uzywane', 'condition' => AdCondition::Used]);
    Ad::factory()->create(['title' => 'Uszkodzone', 'condition' => AdCondition::Damaged]);

    $response = $this->getJson('/api/v1/ads?condition=new,damaged')->assertOk();

    expect($response->json('data.*.title'))->toHaveCount(2)
        ->and($response->json('data.*.title'))->not->toContain('Uzywane');
});

it('matches an ad offering any one of the selected delivery methods', function (): void {
    Ad::factory()->create([
        'title' => 'Kurier i paczkomat',
        'delivery_methods' => [DeliveryMethod::Courier->value, DeliveryMethod::ParcelLocker->value],
    ]);
    Ad::factory()->create([
        'title' => 'Tylko odbior osobisty',
        'delivery_methods' => [DeliveryMethod::Personal->value],
    ]);

    // jsonb_exists_any: „którakolwiek z tych metod”, nie „wszystkie”.
    $this->getJson('/api/v1/ads?delivery=parcel_locker')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Kurier i paczkomat');

    $this->getJson('/api/v1/ads?delivery=personal,post')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Tylko odbior osobisty');
});

it('exposes the delivery methods on the listing payload', function (): void {
    Ad::factory()->create(['delivery_methods' => [DeliveryMethod::Courier->value]]);

    $this->getJson('/api/v1/ads')
        ->assertOk()
        ->assertJsonPath('data.0.delivery_methods', ['courier']);
});

it('rejects an unknown condition or delivery method', function (): void {
    $this->getJson('/api/v1/ads?condition=zepsute')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('condition');

    $this->getJson('/api/v1/ads?delivery=teleportacja')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('delivery');
});

it('combines the offer filters with the category subtree', function (): void {
    $ad = Ad::factory()->create(['is_negotiable' => true, 'price' => 500]);
    Ad::factory()->create(['is_negotiable' => false]);

    $rootSlug = $ad->category->ancestors()->sole()->slug;

    $this->getJson("/api/v1/ads?category={$rootSlug}&negotiable=1&price_max=1000")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ad->id);
});

it('stores the negotiability, condition and delivery chosen in the form', function (): void {
    $payload = validAdPayload(leafCategory(), [
        'is_negotiable' => true,
        'condition' => 'used',
        'delivery_methods' => ['courier', 'personal'],
        'delivery_prices' => ['courier' => '18.99'],
        'district' => 'Mokotów',
    ]);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', $payload)
        ->assertCreated()
        ->assertJsonPath('data.is_negotiable', true)
        ->assertJsonPath('data.condition', 'used')
        ->assertJsonPath('data.district', 'Mokotów')
        ->assertJsonPath('data.delivery_methods', ['courier', 'personal'])
        ->assertJsonPath('data.delivery_prices.courier', '18.99');
});

it('drops an empty delivery price instead of storing it as free', function (): void {
    $payload = validAdPayload(leafCategory(), [
        'delivery_methods' => ['personal'],
        'delivery_prices' => ['personal' => ''],
    ]);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', $payload)
        ->assertCreated()
        ->assertJsonPath('data.delivery_prices', []);
});

it('rejects a delivery price for a method the seller did not choose', function (): void {
    $payload = validAdPayload(leafCategory(), [
        'delivery_methods' => ['personal'],
        'delivery_prices' => ['courier' => '18.99'],
    ]);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/ads', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('delivery_prices.courier');
});
