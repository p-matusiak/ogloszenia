<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepository;
use App\Repositories\Contracts\MessageRepository;

it('creates a conversation for an ad and buyer', function (): void {
    $repo = app(ConversationRepository::class);
    $buyer = User::factory()->create();
    $ad = Ad::factory()->create();

    $conversation = $repo->createForAd($ad, $buyer);

    expect($conversation->ad_id)->toBe($ad->id)
        ->and($conversation->buyer_id)->toBe($buyer->id)
        ->and($conversation->seller_id)->toBe($ad->user_id);
});

it('records a message preview on the conversation', function (): void {
    $conversations = app(ConversationRepository::class);
    $messages = app(MessageRepository::class);
    $buyer = User::factory()->create();
    $ad = Ad::factory()->create();
    $conversation = $conversations->createForAd($ad, $buyer);

    $message = $messages->create($conversation, $buyer, 'Czy produkt jest jeszcze dostępny?');
    $conversations->recordMessage($conversation, $message);

    $conversation->refresh();

    expect($conversation->last_sender_id)->toBe($buyer->id)
        ->and($conversation->last_message_preview)->toBe('Czy produkt jest jeszcze dostępny?')
        ->and($conversation->last_message_at)->not->toBeNull();
});

it('paginates only conversations with messages for the participant', function (): void {
    $repo = app(ConversationRepository::class);
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller, 'user')->create();

    $withMessage = $repo->createForAd($ad, $buyer);
    $withMessage->update(['last_message_at' => now()]);

    $otherAd = Ad::factory()->for($seller, 'user')->create();
    Conversation::query()->create([
        'ad_id' => $otherAd->id,
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
    ]);

    $page = $repo->paginateForUser($buyer);

    expect($page->count())->toBe(1)
        ->and($page->first()?->id)->toBe($withMessage->id);
});

it('counts unread conversations for the recipient', function (): void {
    $repo = app(ConversationRepository::class);
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller, 'user')->create();
    $conversation = $repo->createForAd($ad, $buyer);

    $conversation->update([
        'last_sender_id' => $seller->id,
        'last_message_at' => now(),
        'seller_last_read_at' => now(),
    ]);

    expect($repo->unreadCountFor($buyer))->toBe(1)
        ->and($repo->unreadCountFor($seller))->toBe(0);
});

it('clears unread state when the participant reads the thread', function (): void {
    $repo = app(ConversationRepository::class);
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller, 'user')->create();
    $conversation = $repo->createForAd($ad, $buyer);

    $conversation->update([
        'last_sender_id' => $seller->id,
        'last_message_at' => now(),
    ]);

    $repo->markReadForParticipant($conversation, $buyer);

    expect($repo->unreadCountFor($buyer))->toBe(0);
});
