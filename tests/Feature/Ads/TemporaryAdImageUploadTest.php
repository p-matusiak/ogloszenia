<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

it('uploads temporary ad images and returns their preview metadata', function (): void {
    $response = $this->actingAs(User::factory()->create())
        ->post('/api/v1/ads/temp-images', [
            'images' => [
                UploadedFile::fake()->image('pierwsze.jpg'),
                UploadedFile::fake()->image('drugie.png'),
            ],
        ])
        ->assertCreated()
        ->assertJsonCount(2, 'data');

    $uploads = $response->json('data');

    expect($uploads)->toBeArray()
        ->and($uploads[0]['token'] ?? null)->not->toBeNull()
        ->and($uploads[0]['preview_url'] ?? null)->toContain('/storage/')
        ->and($uploads[0]['original_name'] ?? null)->toBe('pierwsze.jpg')
        ->and($uploads[1]['original_name'] ?? null)->toBe('drugie.png');

    $stored = Storage::disk('public')->allFiles('ads/tmp');

    expect($stored)->toHaveCount(2);
});

it('creates an ad from temporary images and moves them to the final directory in submitted order', function (): void {
    $user = User::factory()->create();

    $uploadResponse = $this->actingAs($user)
        ->post('/api/v1/ads/temp-images', [
            'images' => [
                UploadedFile::fake()->image('pierwsze.jpg'),
                UploadedFile::fake()->image('drugie.png'),
            ],
        ])
        ->assertCreated();

    $uploads = $uploadResponse->json('data');
    $firstToken = $uploads[0]['token'];
    $secondToken = $uploads[1]['token'];

    $this->actingAs($user)
        ->postJson('/api/v1/ads', validAdPayload(leafCategory(), [
            'temporary_images' => [$secondToken, $firstToken],
        ]))
        ->assertCreated()
        ->assertJsonCount(2, 'data.images')
        ->assertJsonPath('data.images.0.is_primary', true);

    $ad = Ad::query()->with('images')->sole();
    $orderedImages = $ad->images->sortBy('position')->values();

    expect($orderedImages[0]->original_name)->toBe('drugie.png')
        ->and($orderedImages[1]->original_name)->toBe('pierwsze.jpg')
        ->and($orderedImages[0]->position)->toBe(0)
        ->and($orderedImages[1]->position)->toBe(1);

    foreach ($orderedImages as $image) {
        Storage::disk('public')->assertExists($image->path);
    }

    expect(Storage::disk('public')->allFiles('ads/tmp'))->toBe([]);
});

it('rejects a temporary image token that belongs to another user', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $uploadResponse = $this->actingAs($owner)
        ->post('/api/v1/ads/temp-images', [
            'images' => [UploadedFile::fake()->image('sekret.jpg')],
        ])
        ->assertCreated();

    $token = $uploadResponse->json('data.0.token');

    $this->actingAs($intruder)
        ->postJson('/api/v1/ads', validAdPayload(leafCategory(), [
            'temporary_images' => [$token],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('temporary_images.0');
});

it('deletes a temporary image when the user removes it before saving the ad', function (): void {
    $user = User::factory()->create();

    $uploadResponse = $this->actingAs($user)
        ->post('/api/v1/ads/temp-images', [
            'images' => [UploadedFile::fake()->image('usun-mnie.jpg')],
        ])
        ->assertCreated();

    $token = $uploadResponse->json('data.0.token');

    expect(Storage::disk('public')->allFiles('ads/tmp'))->toHaveCount(1);

    $this->actingAs($user)
        ->delete("/api/v1/ads/temp-images/{$token}")
        ->assertNoContent();

    expect(Storage::disk('public')->allFiles('ads/tmp'))->toBe([]);
});
