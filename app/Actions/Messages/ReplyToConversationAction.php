<?php

declare(strict_types=1);

namespace App\Actions\Messages;

use App\Events\MessageWasSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepository;
use App\Repositories\Contracts\MessageRepository;
use Illuminate\Support\Facades\DB;

final readonly class ReplyToConversationAction
{
    public function __construct(
        private ConversationRepository $conversations,
        private MessageRepository $messages,
    ) {}

    public function execute(User $sender, Conversation $conversation, string $body): Message
    {
        $message = DB::transaction(function () use ($sender, $conversation, $body): Message {
            $message = $this->messages->create($conversation, $sender, $body);
            $this->conversations->recordMessage($conversation, $message);
            $this->conversations->markReadForParticipant($conversation, $sender);

            event(new MessageWasSent($conversation, $message, $sender));

            return $message->load('sender');
        });

        return $message;
    }
}
