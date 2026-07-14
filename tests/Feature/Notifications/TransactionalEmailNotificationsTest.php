<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Enums\SettingKey;
use App\Models\Ad;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\AdActivated;
use App\Notifications\AdExpired;
use App\Notifications\NewConversationMessage;
use App\Notifications\VerifyEmailAddress;
use App\Services\Contracts\SettingsRepository;
use Illuminate\Support\Facades\Notification;

it('sends an activation mail when an account is registered', function (): void {
    Notification::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'password' => 'sekretne-haslo-123',
        'password_confirmation' => 'sekretne-haslo-123',
    ])->assertCreated();

    $user = User::query()->where('email', 'jan@example.com')->sole();

    Notification::assertSentTo($user, VerifyEmailAddress::class);
});

it('emails the seller when a buyer starts a conversation', function (): void {
    Notification::fake();

    $buyer = User::factory()->create(['name' => 'Kupujący']);
    $seller = User::factory()->create(['name' => 'Sprzedający']);
    $ad = Ad::factory()->for($seller, 'user')->create(['title' => 'Rower miejski']);

    $this->actingAs($buyer)
        ->postJson("/api/v1/ads/{$ad->slug}/messages", [
            'body' => 'Czy mogę odebrać jutro?',
        ])
        ->assertCreated();

    Notification::assertSentTo($seller, NewConversationMessage::class);
    Notification::assertNotSentTo($buyer, NewConversationMessage::class);
});

it('emails the buyer when the seller replies', function (): void {
    Notification::fake();

    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller, 'user')->create();
    $conversation = Conversation::query()->create([
        'ad_id' => $ad->id,
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'last_message_at' => now()->subHour(),
        'last_sender_id' => $buyer->id,
        'last_message_preview' => 'Pierwsza wiadomość',
    ]);

    $this->actingAs($seller)
        ->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'body' => 'Tak, proszę po godzinie 18.',
        ])
        ->assertCreated();

    Notification::assertSentTo($buyer, NewConversationMessage::class);
    Notification::assertNotSentTo($seller, NewConversationMessage::class);
});

it('emails the seller when a pending ad is approved', function (): void {
    Notification::fake();

    $seller = User::factory()->create();
    $ad = Ad::factory()->for($seller)->pending()->create(['title' => 'Laptop Dell']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/ads/{$ad->slug}/approve")
        ->assertOk();

    Notification::assertSentTo($seller, AdActivated::class);
});

it('emails the owner when auto-approval publishes a new ad', function (): void {
    Notification::fake();

    app(SettingsRepository::class)->setEnabled(SettingKey::AutoApproveAds, true);

    $owner = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($owner)
        ->postJson('/api/v1/ads', validAdPayload($category, ['title' => 'Konsola PS5']))
        ->assertCreated()
        ->assertJsonPath('data.status', AdStatus::Active->value);

    Notification::assertSentTo($owner, AdActivated::class);
});

it('emails the owner when a lapsed ad is swept to expired', function (): void {
    Notification::fake();

    $owner = User::factory()->create();
    $ad = Ad::factory()->for($owner)->lapsed()->create(['title' => 'Sofa rozkładana']);

    $this->artisan('ads:expire')->assertSuccessful();

    expect($ad->refresh()->status)->toBe(AdStatus::Expired);
    Notification::assertSentTo($owner, AdExpired::class);
});
