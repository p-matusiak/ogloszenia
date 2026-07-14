<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Repositories\Contracts\AdRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Support\Facades\DB;

final class DeleteAccountAction
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AdRepository $ads,
    ) {}

    public function execute(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->ads->softDeleteAllOwnedByUser($user->id);
            $this->users->softDeleteAccount($user);
        });
    }
}
