<?php

declare(strict_types=1);

use App\Enums\EmailVerificationStatus;
use App\Exceptions\Domain\DomainException;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\RedirectLegacyCategoryFilters;
use App\Http\Middleware\SetAppLocale;
use App\Support\EmailVerificationRedirect;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // TrustProxies is already in the default global stack; it is configured
        // in AppServiceProvider, because config() is not yet bound here.

        // Cookie-based SPA authentication: the Vue app shares a session with the
        // API rather than keeping a bearer token in JavaScript.
        $middleware->statefulApi();

        // withRouting() installs `redirectGuestsTo(fn () => route('login'))`
        // whenever web routes exist, and this app has no `login` route: an
        // unauthenticated API request would blow up with RouteNotFoundException
        // instead of answering 401. Returning null keeps the API JSON-only.
        $middleware->redirectGuestsTo(
            fn (Request $request): ?string => $request->is('api/*') ? null : '/logowanie',
        );

        // `verified` overrides Laravel's own alias, whose middleware redirects
        // to a `verification.notice` route this API-only backend never defines.
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);

        // Kategorie mają dziś własne adresy; stare `/?category=` musi oddać 301.
        $middleware->appendToGroup('web', RedirectLegacyCategoryFilters::class);

        $middleware->appendToGroup('web', SetAppLocale::class);
        $middleware->appendToGroup('api', SetAppLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (DomainException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'code' => $e->errorCode(),
                'message' => $e->getMessage(),
                'details' => (object) $e->details(),
            ], $e->httpStatus());
        });

        // An activation link is opened from a mail client. A stale or tampered
        // one must land on a page explaining what happened, not on a bare 403.
        $exceptions->render(function (InvalidSignatureException $e, Request $request): ?RedirectResponse {
            if (! $request->routeIs('verification.verify')) {
                return null;
            }

            return redirect()->to(
                (new EmailVerificationRedirect)->to(EmailVerificationStatus::ExpiredLink),
            );
        });

        // Without this, an unauthenticated request that does not ask for JSON
        // (a browser address bar, a crawler) makes Laravel redirect to a
        // `login` route this API-only app never defines, producing a 500.
        $exceptions->render(function (AuthenticationException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'code' => 'UNAUTHENTICATED',
                'message' => 'Unauthenticated.',
                'details' => (object) [],
            ], Response::HTTP_UNAUTHORIZED);
        });
    })->create();
