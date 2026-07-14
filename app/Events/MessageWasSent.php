<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Kupujący lub sprzedający wysłał wiadomość w wątku przy ogłoszeniu. */
final class MessageWasSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public Message $message,
        public User $sender,
    ) {}
}
