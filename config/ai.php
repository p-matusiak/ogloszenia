<?php

declare(strict_types=1);

return [
    /*
     * Global switch. Bez klucza API moderator i sugerowanie kategorii i tak
     * działają w trybie „niedostępne” — ta flaga pozwala wyłączyć AI bez
     * kasowania klucza.
     */
    'enabled' => (bool) env('AI_ENABLED', false),

    'openai_api_key' => env('OPENAI_API_KEY'),

    'moderation_model' => env('AI_MODERATION_MODEL', 'omni-moderation-latest'),

    'category_model' => env('AI_CATEGORY_MODEL', 'gpt-4o-mini'),

    'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 15),
];
