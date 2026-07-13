<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Enums\SettingKey;

interface SettingsRepository
{
    public function isEnabled(SettingKey $key): bool;

    public function setEnabled(SettingKey $key, bool $enabled): void;
}
