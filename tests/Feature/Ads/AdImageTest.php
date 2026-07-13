<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\AdImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

it('stores every uploaded image and makes the first one primary', function (): void {
    $payload = validAdPayload(leafCategory(), [
        'images' => [
            UploadedFile::fake()->image('pierwsze.jpg'),
            UploadedFile::fake()->image('drugie.png'),
        ],
    ]);

    $this->actingAs(User::factory()->create())
        ->post('/api/v1/ads', $payload)
        ->assertCreated()
        ->assertJsonCount(2, 'data.images')
        ->assertJsonPath('data.images.0.is_primary', true)
        ->assertJsonPath('data.images.1.is_primary', false);

    $ad = Ad::query()->sole();
    expect($ad->images)->toHaveCount(2);

    foreach ($ad->images as $image) {
        Storage::disk('public')->assertExists($image->path);
    }
});

it('caps an ad at twelve images', function (): void {
    $images = array_map(
        fn (int $i): UploadedFile => UploadedFile::fake()->image("zdjecie-{$i}.jpg"),
        range(1, 13),
    );

    $this->actingAs(User::factory()->create())
        ->post('/api/v1/ads', validAdPayload(leafCategory(), ['images' => $images]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('images');
});

it('rejects a file that is not an image', function (): void {
    $payload = validAdPayload(leafCategory(), [
        'images' => [UploadedFile::fake()->create('zlosliwy.php', 16, 'application/x-php')],
    ]);

    $this->actingAs(User::factory()->create())
        ->post('/api/v1/ads', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('images.0');
});

it('rejects an image larger than the configured limit', function (): void {
    $oversized = UploadedFile::fake()->image('ogromne.jpg')->size(10241);

    $this->actingAs(User::factory()->create())
        ->post('/api/v1/ads', validAdPayload(leafCategory(), ['images' => [$oversized]]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('images.0');
});

it('deletes removed images and closes the gap in their positions', function (): void {
    $user = User::factory()->create();
    $ad = Ad::factory()->for($user)->create();

    $first = AdImage::factory()->for($ad)->create(['position' => 0]);
    $second = AdImage::factory()->for($ad)->create(['position' => 1]);
    $third = AdImage::factory()->for($ad)->create(['position' => 2]);

    $this->actingAs($user)
        ->post("/api/v1/ads/{$ad->slug}", validAdPayload($ad->category, [
            'title' => $ad->title,
            'removed_image_ids' => [$first->id],
        ]))
        ->assertOk();

    expect(AdImage::query()->find($first->id))->toBeNull();

    // The survivors must renumber so exactly one image sits at position 0.
    expect($second->refresh()->position)->toBe(0)
        ->and($third->refresh()->position)->toBe(1);
});
