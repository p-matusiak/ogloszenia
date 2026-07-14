<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Auth\AuthenticateOAuthUserAction;
use App\Enums\OAuthProvider;
use App\Support\OAuthConfigurator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Exceptions\DriverMissingConfigurationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class OAuthController extends Controller
{
    public function __construct(private readonly OAuthConfigurator $oauth) {}

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $oauthProvider = $this->resolveProvider($provider);

        if (! $this->oauth->isConfigured($oauthProvider)) {
            return redirect()->to($this->loginPathWithError($request, 'unconfigured'));
        }

        if ($request->filled('redirect')) {
            $request->session()->put(
                'oauth_redirect',
                $this->safeRedirectPath($request->string('redirect')->toString()),
            );
        }

        try {
            return redirect($this->oauth->driver($oauthProvider)->redirect()->getTargetUrl());
        } catch (DriverMissingConfigurationException) {
            return redirect()->to($this->loginPathWithError($request, 'unconfigured'));
        }
    }

    public function callback(
        Request $request,
        string $provider,
        AuthenticateOAuthUserAction $authenticateOAuthUser,
    ): RedirectResponse {
        $oauthProvider = $this->resolveProvider($provider);

        if (! $this->oauth->isConfigured($oauthProvider)) {
            return redirect()->to($this->loginPathWithError($request, 'unconfigured'));
        }

        try {
            $socialUser = $this->oauth->driver($oauthProvider)->user();
            $user = $authenticateOAuthUser->execute($oauthProvider, $socialUser);

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->to($request->session()->pull('oauth_redirect', '/'));
        } catch (UnprocessableEntityHttpException $exception) {
            Log::warning('OAuth callback rejected', [
                'provider' => $oauthProvider->value,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->to($this->loginPathWithError($request, 'email_required'));
        } catch (\Throwable $exception) {
            Log::warning('OAuth callback failed', [
                'provider' => $oauthProvider->value,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->to($this->loginPathWithError($request, 'failed'));
        }
    }

    private function resolveProvider(string $provider): OAuthProvider
    {
        $resolved = OAuthProvider::tryFromDriver($provider);

        if ($resolved === null) {
            throw new NotFoundHttpException;
        }

        return $resolved;
    }

    private function safeRedirectPath(string $path): string
    {
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '/';
        }

        return $path;
    }

    private function loginPathWithError(Request $request, string $reason): string
    {
        $redirect = $request->session()->pull('oauth_redirect');

        $query = http_build_query(array_filter([
            'oauth_error' => $reason,
            'redirect' => is_string($redirect) && $redirect !== '/' ? $redirect : null,
        ]));

        return '/logowanie'.($query !== '' ? '?'.$query : '');
    }
}
