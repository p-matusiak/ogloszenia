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
     * @param  array<string, mixed>  $data  Validated name, phone and bio.
     */
    public function execute(
        User $user,
        array $data,
        ?UploadedFile $avatar = null,
        bool $removeAvatar = false,
    ): User {
        return $this->users->updateAttributes(
            $user,
            $data + $this->avatarAttributes($user, $avatar, $removeAvatar),
        );
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
