<?php

declare(strict_types=1);

namespace App\Actions\Messages;

use App\Events\MessageWasSent;
use App\Exceptions\Domain\AdNotMessageableException;
use App\Exceptions\Domain\CannotMessageOwnAdException;
use App\Models\Ad;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepository;
use App\Repositories\Contracts\MessageRepository;
use Illuminate\Support\Facades\DB;

final readonly class SendAdMessageAction
{
    public function __construct(
        private ConversationRepository $conversations,
        private MessageRepository $messages,
    ) {}

    /**
     * Kupujący otwiera lub kontynuuje wątek przy ogłoszeniu.
     *
     * @throws AdNotMessageableException
     * @throws CannotMessageOwnAdException
     */
    public function execute(User $buyer, Ad $ad, string $body): Conversation
    {
        if ($ad->user_id === $buyer->id) {
            throw new CannotMessageOwnAdException;
        }

        if (! $ad->isPubliclyVisible()) {
            throw new AdNotMessageableException;
        }

        $conversation = DB::transaction(function () use ($buyer, $ad, $body): Conversation {
            $conversation = $this->conversations->findForAdAndBuyer($ad, $buyer)
                ?? $this->conversations->createForAd($ad, $buyer);

            $message = $this->messages->create($conversation, $buyer, $body);
            $this->conversations->recordMessage($conversation, $message);
            $this->conversations->markReadForParticipant($conversation, $buyer);

            event(new MessageWasSent($conversation, $message, $buyer));

            return $conversation->fresh(['ad.category.ancestors', 'ad.primaryImage', 'buyer', 'seller']);
        });

        return $conversation;
    }
}
