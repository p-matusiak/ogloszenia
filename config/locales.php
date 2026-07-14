<?php

declare(strict_types=1);

use App\Enums\AppLocale;

return [
    'default' => env('APP_LOCALE', AppLocale::Polish->value),

    'fallback' => env('APP_FALLBACK_LOCALE', AppLocale::English->value),

    /** @var list<string> */
    'supported' => AppLocale::values(),
];
