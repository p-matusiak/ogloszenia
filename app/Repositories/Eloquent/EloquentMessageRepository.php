<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\Contracts\MessageRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Support\Facades\Config;

final class EloquentMessageRepository implements MessageRepository
{
    public function create(Conversation $conversation, User $sender, string $body): Message
    {
        return Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => $body,
        ]);
    }

    /**
     * @return LengthAwarePaginatorContract<int, Message>
     */
    public function paginateForConversation(Conversation $conversation): LengthAwarePaginatorContract
    {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('sender')
            ->orderByDesc('created_at')
            ->paginate(Config::integer('messages.messages_per_page'))
            ->withQueryString();
    }
}
