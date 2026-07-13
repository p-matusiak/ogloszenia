<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MessageRepository
{
    public function create(Conversation $conversation, User $sender, string $body): Message;

    /**
     * @return LengthAwarePaginator<int, Message>
     */
    public function paginateForConversation(Conversation $conversation): LengthAwarePaginator;
}
