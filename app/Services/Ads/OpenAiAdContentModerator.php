<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Services\Contracts\AdContentModerator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class OpenAiAdContentModerator implements AdContentModerator
{
    private const string REJECTION_SEXUAL = 'Treść zawiera niedozwolone materiały o charakterze seksualnym.';

    private const string REJECTION_VULGAR = 'Treść zawiera wulgaryzmy lub niedozwolone sformułowania.';

    public function isAvailable(): bool
    {
        return Config::boolean('ai.enabled') && filled(Config::get('ai.openai_api_key'));
    }

    public function review(string $title, string $description): AdModerationResult
    {
        if (! $this->isAvailable()) {
            return AdModerationResult::unavailable();
        }

        try {
            $response = Http::withToken((string) Config::get('ai.openai_api_key'))
                ->timeout(Config::integer('ai.timeout_seconds'))
                ->acceptJson()
                ->post('https://api.openai.com/v1/moderations', [
                    'input' => trim($title)."\n\n".trim($description),
                    'model' => Config::string('ai.moderation_model'),
                ]);

            if (! $response->successful()) {
                Log::warning('OpenAI moderation request failed.', [
                    'status' => $response->status(),
                ]);

                return AdModerationResult::unavailable();
            }

            /** @var array<string, mixed> $result */
            $result = $response->json('results.0', []);

            return $this->resultFromPayload($result);
        } catch (\Throwable $exception) {
            Log::warning('OpenAI moderation unavailable.', [
                'message' => $exception->getMessage(),
            ]);

            return AdModerationResult::unavailable();
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function resultFromPayload(array $result): AdModerationResult
    {
        if (($result['flagged'] ?? false) !== true) {
            return AdModerationResult::approved();
        }

        /** @var array<string, bool> $categories */
        $categories = $result['categories'] ?? [];

        if (($categories['sexual'] ?? false) || ($categories['sexual/minors'] ?? false)) {
            return AdModerationResult::rejected(self::REJECTION_SEXUAL);
        }

        return AdModerationResult::rejected(self::REJECTION_VULGAR);
    }
}
