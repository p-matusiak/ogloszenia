<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

final class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->involves($user);
    }

    public function reply(User $user, Conversation $conversation): bool
    {
        return $conversation->involves($user);
    }
}
