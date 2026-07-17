<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\RequestPasswordResetAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

final class PasswordResetController extends Controller
{
    public function __construct(
        private readonly RequestPasswordResetAction $requestPasswordReset,
        private readonly ResetPasswordAction $resetPassword,
    ) {}

    /**
     * Always reports success: a differing response would let an attacker
     * enumerate which addresses hold accounts.
     */
    public function sendLink(ForgotPasswordRequest $request): JsonResponse
    {
        $this->requestPasswordReset->execute((string) $request->validated('email'));

        return response()->json(['message' => __('passwords.sent')]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        /** @var array{email: string, password: string, password_confirmation: string, token: string} $data */
        $data = $request->validated();
        $this->resetPassword->execute($data);

        return response()->json(['message' => __('passwords.reset')]);
    }
}
