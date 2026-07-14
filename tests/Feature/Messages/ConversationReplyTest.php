<?php

declare(strict_types=1);

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Contracts\MessageRepository;

it('paginates conversations with a cursor instead of page numbers', function (): void {
    config(['messages.conversations_per_page' => 2]);

    $buyer = User::factory()->create();
    $seller = User::factory()->create();

    foreach ([3, 2, 1] as $minutesAgo) {
        $ad = Ad::factory()->for($seller, 'user')->create();
        Conversation::query()->create([
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'last_message_at' => now()->subMinutes($minutesAgo),
            'last_sender_id' => $seller->id,
            'last_message_preview' => "Wiadomość {$minutesAgo}",
        ]);
    }

    $first = $this->actingAs($buyer)
        ->getJson('/api/v1/my/conversations')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonMissingPath('meta.last_page')
        ->assertJsonMissingPath('meta.total');

    $nextCursor = $first->json('meta.next_cursor');
    expect($nextCursor)->toBeString();

    $this->actingAs($buyer)
        ->getJson('/api/v1/my/conversations?'.http_build_query(['cursor' => $nextCursor]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.next_cursor', null);
});

it('lists conversations for a participant', function (): void {
    $buyer = User::factory()->create(['avatar_path' => 'avatars/buyer.jpg']);
    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller, 'user')->create();
    Conversation::query()->create([
        'ad_id' => $ad->id,
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'last_message_at' => now(),
        'last_sender_id' => $buyer->id,
        'last_message_preview' => 'Pytanie o odbiór',
    ]);

    $this->actingAs($seller)
        ->getJson('/api/v1/my/conversations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ad.slug', $ad->slug)
        ->assertJsonPath('data.0.is_unread', true)
        ->assertJsonPath('data.0.other_party.avatar_url', fn (string $url): bool => str_contains($url, '/storage/avatars/buyer.jpg'));
});

it('lets the seller reply in an existing conversation', function (): void {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller, 'user')->create();
    $conversation = Conversation::query()->create([
        'ad_id' => $ad->id,
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'last_message_at' => now()->subHour(),
        'last_sender_id' => $buyer->id,
        'last_message_preview' => 'Czy mogę odebrać?',
    ]);
    app(MessageRepository::class)->create($conversation, $buyer, 'Czy mogę odebrać?');

    $this->actingAs($seller)
        ->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'body' => 'Tak, proszę dzwonić przed przyjazdem.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Tak, proszę dzwonić przed przyjazdem.')
        ->assertJsonPath('data.is_mine', true);

    expect($conversation->fresh()?->last_sender_id)->toBe($seller->id);
});

it('lets the buyer reply in an existing conversation', function (): void {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller, 'user')->create();
    $conversation = Conversation::query()->create([
        'ad_id' => $ad->id,
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'last_message_at' => now()->subHour(),
        'last_sender_id' => $seller->id,
        'last_message_preview' => 'Odbiór możliwy jutro',
    ]);

    $this->actingAs($buyer)
        ->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'body' => 'Super, będę około 18:00.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.is_mine', true);
});

it('forbids outsiders from reading a conversation', function (): void {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $outsider = User::factory()->create();
    $ad = Ad::factory()->for($seller, 'user')->create();
    $conversation = Conversation::query()->create([
        'ad_id' => $ad->id,
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'last_message_at' => now(),
        'last_sender_id' => $buyer->id,
    ]);

    $this->actingAs($outsider)
        ->getJson("/api/v1/conversations/{$conversation->id}")
        ->assertForbidden();
});

it('returns paginated messages for a participant', function (): void {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller, 'user')->create();
    $conversation = Conversation::query()->create([
        'ad_id' => $ad->id,
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
    ]);
    $messages = app(MessageRepository::class);
    $messages->create($conversation, $buyer, 'Pierwsza');
    $second = $messages->create($conversation, $seller, 'Druga');
    $second->update(['created_at' => now()->addMinute()]);

    $this->actingAs($buyer)
        ->getJson("/api/v1/conversations/{$conversation->id}/messages")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.body', 'Druga');
});

it('exposes unread count for the current user', function (): void {
    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller, 'user')->create();
    Conversation::query()->create([
        'ad_id' => $ad->id,
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'last_message_at' => now(),
        'last_sender_id' => $seller->id,
        'last_message_preview' => 'Odpowiedź',
    ]);

    $this->actingAs($buyer)
        ->getJson('/api/v1/my/conversations/unread-count')
        ->assertOk()
        ->assertJsonPath('data.count', 1);
});
