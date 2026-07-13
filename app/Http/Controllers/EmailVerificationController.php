<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Auth\VerifyEmailAction;
use App\Enums\EmailVerificationStatus;
use App\Models\User;
use App\Support\EmailVerificationRedirect;
use Illuminate\Http\RedirectResponse;

/**
 * A web route, not an API one: the link is opened from a mail client, so the
 * response has to be a page a human can read. Signature validation happens in
 * the `signed` middleware before this ever runs.
 */
final class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly VerifyEmailAction $verifyEmail,
        private readonly EmailVerificationRedirect $redirect,
    ) {}

    public function __invoke(string $id, string $hash): RedirectResponse
    {
        $user = User::query()->find($id);

        $status = $user instanceof User
            ? $this->verifyEmail->execute($user, $hash)
            : EmailVerificationStatus::InvalidLink;

        return redirect()->to($this->redirect->to($status));
    }
}
