<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Ads\NullAdCategorySuggester;
use App\Services\Ads\NullAdContentModerator;
use App\Services\Ads\OpenAiAdCategorySuggester;
use App\Services\Ads\OpenAiAdContentModerator;
use App\Services\Contracts\AdCategorySuggester;
use App\Services\Contracts\AdContentModerator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

final class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdContentModerator::class, function (): AdContentModerator {
            if (Config::boolean('ai.enabled') && filled(Config::get('ai.openai_api_key'))) {
                return $this->app->make(OpenAiAdContentModerator::class);
            }

            return $this->app->make(NullAdContentModerator::class);
        });

        $this->app->singleton(AdCategorySuggester::class, function (): AdCategorySuggester {
            if (Config::boolean('ai.enabled') && filled(Config::get('ai.openai_api_key'))) {
                return $this->app->make(OpenAiAdCategorySuggester::class);
            }

            return $this->app->make(NullAdCategorySuggester::class);
        });
    }
}
