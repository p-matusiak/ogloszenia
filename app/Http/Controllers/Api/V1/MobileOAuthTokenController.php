<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ExchangeMobileOAuthCodeRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class MobileOAuthTokenController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function __invoke(ExchangeMobileOAuthCodeRequest $request): JsonResponse
    {
        $code = $request->string('code')->toString();
        $userId = Cache::pull('mobile_oauth:'.$code);
        $user = is_numeric($userId) ? User::query()->find((int) $userId) : null;

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'code' => __('auth.oauth_code_invalid'),
            ]);
        }

        $device = $request->string('device_name')->toString();
        $user->tokens()->where('name', $device)->delete();

        return response()->json([
            'token' => $user->createToken($device)->plainTextToken,
            'data' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }
}
