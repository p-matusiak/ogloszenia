<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface ConversationRepository
{
    public function findForParticipant(User $user, int $conversationId): ?Conversation;

    public function findForAdAndBuyer(Ad $ad, User $buyer): ?Conversation;

    public function createForAd(Ad $ad, User $buyer): Conversation;

    /**
     * Keyset pagination po (last_message_at, id) — bez COUNT(*) i last_page.
     *
     * @return CursorPaginator<int, Conversation>
     */
    public function paginateForUser(User $user, ?string $encodedCursor = null): CursorPaginator;

    public function recordMessage(Conversation $conversation, Message $message): void;

    public function markReadForParticipant(Conversation $conversation, User $user): void;

    public function unreadCountFor(User $user): int;
}
