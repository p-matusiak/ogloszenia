<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\MessageWasSent;
use App\Notifications\NewConversationMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/** Mail do drugiej strony wątku — nie blokuje zapisu wiadomości. */
final class NotifyRecipientOfNewMessage implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MessageWasSent $event): void
    {
        $conversation = $event->conversation->loadMissing(['ad', 'buyer', 'seller']);
        $recipient = $conversation->otherParty($event->sender);

        $recipient->notify(new NewConversationMessage(
            $conversation,
            $event->message,
            $event->sender,
        ));
    }
}
