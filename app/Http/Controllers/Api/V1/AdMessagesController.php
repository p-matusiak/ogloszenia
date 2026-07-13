<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Messages\SendAdMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Messages\SendAdMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AdMessagesController extends Controller
{
    public function store(
        SendAdMessageRequest $request,
        Ad $ad,
        SendAdMessageAction $action,
    ): JsonResponse {
        $conversation = $action->execute(
            $this->currentUser($request),
            $ad,
            $request->validated('body'),
        );

        return response()->json([
            'data' => new ConversationResource($conversation),
        ], Response::HTTP_CREATED);
    }

    private function currentUser(SendAdMessageRequest $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
