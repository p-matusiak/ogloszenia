<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SettingKey;
use App\Models\Setting;
use App\Services\Contracts\SettingsRepository;
use Illuminate\Contracts\Cache\Repository as Cache;

final readonly class CachedSettingsRepository implements SettingsRepository
{
    private const int TTL_SECONDS = 3600;

    public function __construct(private Cache $cache) {}

    public function isEnabled(SettingKey $key): bool
    {
        /** @var bool */
        return $this->cache->remember(
            $this->cacheKey($key),
            self::TTL_SECONDS,
            fn (): bool => $this->readFromDatabase($key),
        );
    }

    public function setEnabled(SettingKey $key, bool $enabled): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key->value],
            ['value' => $enabled],
        );

        // Write-through rather than forget: the very next read is almost always
        // the admin reloading the settings page.
        $this->cache->put($this->cacheKey($key), $enabled, self::TTL_SECONDS);
    }

    private function readFromDatabase(SettingKey $key): bool
    {
        $setting = Setting::query()->find($key->value);

        return $setting === null
            ? $key->default()
            : (bool) $setting->value;
    }

    private function cacheKey(SettingKey $key): string
    {
        return 'settings:'.$key->value;
    }
}
