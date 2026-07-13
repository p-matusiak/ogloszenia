<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if(
            $user === null || ! $user->is_admin,
            Response::HTTP_FORBIDDEN,
            'Ta operacja wymaga uprawnień administratora.',
        );

        return $next($request);
    }
}
