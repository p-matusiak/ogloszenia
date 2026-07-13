<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\User;

it('lets a buyer start a conversation about an active ad', function (): void {
    $buyer = User::factory()->create();
    $ad = Ad::factory()->create();

    $this->actingAs($buyer)
        ->postJson("/api/v1/ads/{$ad->slug}/messages", [
            'body' => 'Dzień dobry, czy mogę odebrać osobiście?',
        ])
        ->assertCreated()
        ->assertJsonPath('data.ad.slug', $ad->slug);

    $conversation = Conversation::query()->first();

    expect($conversation)->not->toBeNull()
        ->and($conversation?->buyer_id)->toBe($buyer->id)
        ->and($conversation?->seller_id)->toBe($ad->user_id)
        ->and($conversation?->messages()->count())->toBe(1);
});

it('reuses an existing conversation for the same buyer and ad', function (): void {
    $buyer = User::factory()->create();
    $ad = Ad::factory()->create();
    $conversation = Conversation::query()->create([
        'ad_id' => $ad->id,
        'buyer_id' => $buyer->id,
        'seller_id' => $ad->user_id,
        'last_message_at' => now()->subDay(),
        'last_sender_id' => $buyer->id,
        'last_message_preview' => 'Pierwsza wiadomość',
    ]);

    $this->actingAs($buyer)
        ->postJson("/api/v1/ads/{$ad->slug}/messages", [
            'body' => 'Druga wiadomość',
        ])
        ->assertCreated()
        ->assertJsonPath('data.id', $conversation->id);

    expect(Conversation::query()->count())->toBe(1)
        ->and($conversation->fresh()?->messages()->count())->toBe(1);
});

it('rejects messaging an inactive ad', function (): void {
    $buyer = User::factory()->create();
    $ad = Ad::factory()->expired()->create();

    $this->actingAs($buyer)
        ->postJson("/api/v1/ads/{$ad->slug}/messages", ['body' => 'Cześć'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'AD_NOT_MESSAGEABLE');
});

it('rejects messaging your own ad', function (): void {
    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller, 'user')->create();

    $this->actingAs($seller)
        ->postJson("/api/v1/ads/{$ad->slug}/messages", ['body' => 'Cześć'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'CANNOT_MESSAGE_OWN_AD');
});

it('requires authentication to message an ad', function (): void {
    $ad = Ad::factory()->create();

    $this->postJson("/api/v1/ads/{$ad->slug}/messages", ['body' => 'Cześć'])
        ->assertUnauthorized();
});
