<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CachedSettingsRepository;
use App\Services\Contracts\SettingsRepository;
use App\Services\Seo\SeoPresenter;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class, CachedSettingsRepository::class);
    }

    public function boot(): void
    {
        $this->configureTrustedProxies();
        $this->configureUrlsBehindProxy();
        $this->configureEloquentStrictness();
        $this->configureSeoDefaults();
    }

    /**
     * Powłokę SPA renderuje też strona błędu 404, która nie przechodzi przez
     * żaden kontroler i nie ma skąd wziąć meta tagów. Bez tego `app.blade.php`
     * wywaliłby się na niezdefiniowanej zmiennej przy każdym nieznanym adresie.
     */
    private function configureSeoDefaults(): void
    {
        View::composer('app', function (ViewContract $view): void {
            if (array_key_exists('meta', $view->getData())) {
                return;
            }

            $view->with('meta', $this->app->make(SeoPresenter::class)->fallback());
        });
    }

    /**
     * The app sits behind Nginx Proxy Manager. Untrusted proxies would make
     * every visitor share one rate-limit bucket, because RateLimiter keys on
     * the client IP, and HTTPS would never be detected from X-Forwarded-Proto.
     */
    private function configureTrustedProxies(): void
    {
        /** @var list<string> $proxies */
        $proxies = Config::array('proxies.trusted');

        if ($proxies === []) {
            return;
        }

        TrustProxies::at($proxies);
        TrustProxies::withHeaders(
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
        );
    }

    /**
     * Za reverse proxy Socialite musi generować callback z https, gdy użytkownik
     * wchodzi przez HTTPS — inaczej Google i Facebook odrzucą redirect_uri.
     */
    private function configureUrlsBehindProxy(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        if (request()->secure()) {
            URL::forceScheme('https');
        }
    }

    private function configureEloquentStrictness(): void
    {
        $strict = ! $this->app->isProduction();

        Model::preventLazyLoading($strict);
        Model::preventSilentlyDiscardingAttributes($strict);
        Model::preventAccessingMissingAttributes($strict);

        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            Log::warning('Eloquent lazy loading violation.', [
                'model' => $model::class,
                'relation' => $relation,
            ]);
        });
    }
}
