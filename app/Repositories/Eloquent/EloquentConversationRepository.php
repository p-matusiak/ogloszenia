<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepository;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

final class EloquentConversationRepository implements ConversationRepository
{
    /**
     * @var list<string>
     */
    private const array LISTING_RELATIONS = ['ad.category.ancestors', 'ad.primaryImage', 'buyer', 'seller'];

    public function findForParticipant(User $user, int $conversationId): ?Conversation
    {
        return Conversation::query()
            ->forParticipant($user)
            ->with(['ad.category.ancestors', 'ad.primaryImage', 'buyer', 'seller'])
            ->find($conversationId);
    }

    public function findForAdAndBuyer(Ad $ad, User $buyer): ?Conversation
    {
        return Conversation::query()
            ->where('ad_id', $ad->id)
            ->where('buyer_id', $buyer->id)
            ->first();
    }

    public function createForAd(Ad $ad, User $buyer): Conversation
    {
        return Conversation::query()->create([
            'ad_id' => $ad->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $ad->user_id,
        ]);
    }

    /**
     * @return CursorPaginatorContract<int, Conversation>
     */
    public function paginateForUser(User $user, ?string $encodedCursor = null): CursorPaginatorContract
    {
        $cursor = $encodedCursor !== null && $encodedCursor !== ''
            ? Cursor::fromEncoded($encodedCursor)
            : null;

        return Conversation::query()
            ->forParticipant($user)
            ->with(self::LISTING_RELATIONS)
            ->whereNotNull('last_message_at')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->cursorPaginate(
                Config::integer('messages.conversations_per_page'),
                ['*'],
                'cursor',
                $cursor,
            );
    }

    public function recordMessage(Conversation $conversation, Message $message): void
    {
        $previewLength = Config::integer('messages.preview_length');

        $conversation->update([
            'last_sender_id' => $message->sender_id,
            'last_message_preview' => Str::limit($message->body, $previewLength),
            'last_message_at' => $message->created_at,
        ]);
    }

    public function markReadForParticipant(Conversation $conversation, User $user): void
    {
        $column = $conversation->buyer_id === $user->id
            ? 'buyer_last_read_at'
            : 'seller_last_read_at';

        $conversation->update([$column => now()]);
    }

    public function unreadCountFor(User $user): int
    {
        return Conversation::query()
            ->forParticipant($user)
            ->whereNotNull('last_message_at')
            ->whereNotNull('last_sender_id')
            ->where('last_sender_id', '!=', $user->id)
            ->where(function (Builder $query) use ($user): void {
                $query->where(function (Builder $inner) use ($user): void {
                    $inner->where('buyer_id', $user->id)
                        ->where(function (Builder $read): void {
                            $read->whereNull('buyer_last_read_at')
                                ->orWhereColumn('last_message_at', '>', 'buyer_last_read_at');
                        });
                })->orWhere(function (Builder $inner) use ($user): void {
                    $inner->where('seller_id', $user->id)
                        ->where(function (Builder $read): void {
                            $read->whereNull('seller_last_read_at')
                                ->orWhereColumn('last_message_at', '>', 'seller_last_read_at');
                        });
                });
            })
            ->count();
    }
}
