<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\Domain\EmailNotVerifiedException;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces Laravel's `verified` alias. The framework's version redirects to a
 * `verification.notice` route this API-only backend never defines; ours raises
 * a domain exception that renders into the shared error envelope.
 */
final class EnsureEmailIsVerified
{
    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws EmailNotVerifiedException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof MustVerifyEmail || ! $user->hasVerifiedEmail()) {
            throw new EmailNotVerifiedException;
        }

        return $next($request);
    }
}
