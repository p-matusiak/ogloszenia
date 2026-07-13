<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PasswordResetController extends Controller
{
    /**
     * Always reports success: a differing response would let an attacker
     * enumerate which addresses hold accounts.
     */
    public function sendLink(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->safe()->only('email'));

        return response()->json(['message' => __('passwords.sent')]);
    }

    /**
     * @throws ValidationException
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->safe()->only(['email', 'password', 'password_confirmation', 'token']),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['message' => __($status)]);
    }
}
