<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\AppLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetAppLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale->value);

        return $next($request);
    }

    private function resolveLocale(Request $request): AppLocale
    {
        $header = $request->header('Accept-Language');

        if (is_string($header) && $header !== '') {
            $fromHeader = AppLocale::tryFromHeader($header);

            if ($fromHeader !== null) {
                return $fromHeader;
            }
        }

        $configured = config('locales.default', AppLocale::Polish->value);

        return AppLocale::tryFrom((string) $configured) ?? AppLocale::Polish;
    }
}
