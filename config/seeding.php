<?php

declare(strict_types=1);

return [
    'ads_total' => (int) env('ADS_SEED_TOTAL', 3_000_000),
    'ads_batch_size' => (int) env('ADS_SEED_BATCH_SIZE', 250),
    'seller_count' => (int) env('ADS_SEED_SELLERS', 250),
    'images_per_ad_min' => (int) env('ADS_SEED_IMAGES_MIN', 2),
    'images_per_ad_max' => (int) env('ADS_SEED_IMAGES_MAX', 3),
];
