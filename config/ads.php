<?php

declare(strict_types=1);

return [
    /*
     * How long an ad stays publicly visible after publication or a refresh.
     */
    'lifetime_days' => (int) env('ADS_LIFETIME_DAYS', 30),

    /*
     * Anti-spam: how many ads a single user may create per calendar day.
     */
    'daily_limit_per_user' => (int) env('ADS_DAILY_LIMIT_PER_USER', 5),

    'images' => [
        'max_per_ad' => (int) env('ADS_MAX_IMAGES', 12),

        /*
         * Kilobytes, matching Laravel's `max:` validation unit. Keep this at or
         * below upload_max_filesize in docker/php/php.ini.
         */
        'max_size_kb' => (int) env('ADS_MAX_IMAGE_KB', 10240),

        'disk' => env('ADS_IMAGE_DISK', 'public'),

        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    'per_page' => 20,

    /*
     * Sekundy życia zapamiętanego COUNT(*) dla publicznej listy. Dokładny licznik
     * na kilku milionach ogłoszeń kosztuje sekundy przy każdym żądaniu, a służy
     * wyłącznie do narysowania paginatora. Zero wyłącza cache.
     */
    'count_cache_ttl' => (int) env('ADS_COUNT_CACHE_TTL', 60),

    /*
     * Powyżej tylu trafień publiczna lista pokazuje szacunek z estymatora
     * planisty zamiast dokładnego COUNT(*) — filtry bez pokrywającego indeksu
     * (delivery, condition, location, q) seq-scanują całą tabelę, a dla listy
     * z milionami wyników liczba wyników i tak jest tylko poglądowa. Zbiory
     * poniżej progu liczymy dokładnie. Zero wyłącza szacowanie.
     */
    'count_estimate_threshold' => (int) env('ADS_COUNT_ESTIMATE_THRESHOLD', 10000),

    /*
     * ISO 4217 dla danych strukturalnych schema.org, symbol dla treści
     * czytanej przez człowieka (kanał RSS, meta description).
     */
    'currency' => 'PLN',
    'currency_symbol' => 'zł',

    /*
     * Accepted values for the "Report ad" form. Kept here rather than as an
     * enum because moderators will want to tune the list without a deploy.
     */
    'report_reasons' => ['spam', 'scam', 'offensive', 'wrong_category', 'other'],
];
