<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
final class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        assert($viewer instanceof User);

        return [
            'id' => $this->id,
            'ad' => new AdSummaryResource($this->whenLoaded('ad')),
            'other_party' => new MessageParticipantResource($this->otherParty($viewer)),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'is_unread' => $this->isUnreadFor($viewer),
        ];
    }
}
