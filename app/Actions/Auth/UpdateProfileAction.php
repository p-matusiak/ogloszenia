<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class UpdateProfileAction
{
    public function __construct(private readonly UserRepository $users) {}

    /**
     * @param  array<string, mixed>  $data  Validated name, email and bio.
     */
    public function execute(
        User $user,
        array $data,
        ?UploadedFile $avatar = null,
        bool $removeAvatar = false,
    ): User {
        $newEmail = isset($data['email']) ? (string) $data['email'] : $user->email;
        $emailChanged = $newEmail !== $user->email;

        $user = $this->users->updateAttributes(
            $user,
            $data + $this->avatarAttributes($user, $avatar, $removeAvatar),
        );

        if ($emailChanged) {
            $this->requireVerificationOfNewAddress($user);
        }

        return $user;
    }

    /**
     * A confirmed address does not vouch for the one that replaced it, so the
     * account drops back to unverified and a fresh link goes out.
     */
    private function requireVerificationOfNewAddress(User $user): void
    {
        // email_verified_at is deliberately not fillable: only this path may
        // clear it, and only the signed activation link may set it.
        $this->users->clearEmailVerification($user);

        $user->sendEmailVerificationNotification();
    }

    /**
     * @return array<string, string|null>
     */
    private function avatarAttributes(User $user, ?UploadedFile $avatar, bool $removeAvatar): array
    {
        if ($avatar === null && ! $removeAvatar) {
            return [];
        }

        if ($user->avatar_path !== null) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        return ['avatar_path' => $avatar?->store('avatars', 'public') ?: null];
    }
}
