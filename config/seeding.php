<?php

declare(strict_types=1);

return [
    'ads_total' => (int) env('ADS_SEED_TOTAL', 3_000_000),
    'ads_batch_size' => (int) env('ADS_SEED_BATCH_SIZE', 250),
    'seller_count' => (int) env('ADS_SEED_SELLERS', 250),
    'images_per_ad_min' => (int) env('ADS_SEED_IMAGES_MIN', 2),
    'images_per_ad_max' => (int) env('ADS_SEED_IMAGES_MAX', 3),
    'demo_ads_per_category' => (int) env('DEMO_ADS_PER_CATEGORY', 100),
    'demo_seller_count' => (int) env('DEMO_SELLERS', 120),
    'demo_batch_size' => (int) env('DEMO_ADS_BATCH_SIZE', 50),
    'demo_fetch_images' => (bool) env('DEMO_FETCH_IMAGES', true),
];
