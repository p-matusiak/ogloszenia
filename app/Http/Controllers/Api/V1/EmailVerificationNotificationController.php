<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\ResendEmailVerificationAction;
use App\Exceptions\Domain\EmailAlreadyVerifiedException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmailVerificationNotificationController extends Controller
{
    public function __construct(
        private readonly ResendEmailVerificationAction $resend,
    ) {}

    /**
     * @throws EmailAlreadyVerifiedException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $this->resend->execute($user);

        return response()->json(['message' => 'Link aktywacyjny został wysłany ponownie.']);
    }
}
