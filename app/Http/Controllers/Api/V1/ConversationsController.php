<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Messages\ReplyToConversationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Messages\ReplyToConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\ConversationSummaryResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepository;
use App\Repositories\Contracts\MessageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class ConversationsController extends Controller
{
    public function index(Request $request, ConversationRepository $conversations): AnonymousResourceCollection
    {
        $cursor = $request->query('cursor');

        return ConversationSummaryResource::collection(
            $conversations->paginateForUser(
                $this->currentUser($request),
                is_string($cursor) ? $cursor : null,
            ),
        );
    }

    public function unreadCount(Request $request, ConversationRepository $conversations): JsonResponse
    {
        return response()->json([
            'data' => [
                'count' => $conversations->unreadCountFor($this->currentUser($request)),
            ],
        ]);
    }

    public function show(
        Request $request,
        Conversation $conversation,
        ConversationRepository $conversations,
    ): ConversationResource {
        $user = $this->currentUser($request);
        $this->authorize('view', $conversation);

        $loaded = $conversations->findForParticipant($user, $conversation->id);
        assert($loaded instanceof Conversation);

        $conversations->markReadForParticipant($loaded, $user);

        return new ConversationResource($loaded);
    }

    public function messages(
        Request $request,
        Conversation $conversation,
        ConversationRepository $conversations,
        MessageRepository $messages,
    ): AnonymousResourceCollection {
        $user = $this->currentUser($request);
        $this->authorize('view', $conversation);

        $loaded = $conversations->findForParticipant($user, $conversation->id);
        assert($loaded instanceof Conversation);

        return MessageResource::collection(
            $messages->paginateForConversation($loaded),
        );
    }

    public function reply(
        ReplyToConversationRequest $request,
        Conversation $conversation,
        ReplyToConversationAction $action,
    ): JsonResponse {
        $message = $action->execute(
            $this->currentUser($request),
            $conversation,
            $request->validated('body'),
        );

        return response()->json([
            'data' => new MessageResource($message),
        ], Response::HTTP_CREATED);
    }

    private function currentUser(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
