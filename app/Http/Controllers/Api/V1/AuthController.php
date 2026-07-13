<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\UpdateProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterUserAction $registerUser,
        private readonly UpdateProfileAction $updateProfile,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->registerUser->execute($request->safe()->only(['name', 'email', 'password']));

        // Signed in straight away, but unverified: the SPA shows an activation
        // banner rather than locking the visitor out of their own account.
        Auth::login($user);
        $request->session()->regenerate();

        return (new UserResource($user))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @throws ValidationException
     */
    public function login(LoginRequest $request): UserResource
    {
        if (! Auth::attempt($request->safe()->only(['email', 'password']))) {
            // One generic message: never reveal whether the address exists.
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        assert($user instanceof User);

        return new UserResource($user);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        $user = $request->user();
        assert($user instanceof User);

        return new UserResource($user);
    }

    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        assert($user instanceof User);

        // Reload so every column is present: actingAs() and the session guard
        // both hand over models that never went through a full select.
        $user = User::query()->findOrFail($user->id);

        $avatar = $request->file('avatar');

        return new UserResource($this->updateProfile->execute(
            user: $user,
            data: $request->safe()->only(['name', 'email', 'phone', 'bio']),
            avatar: $avatar instanceof UploadedFile ? $avatar : null,
            removeAvatar: $request->boolean('remove_avatar'),
        ));
    }
}
