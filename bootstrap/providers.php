<?php

use App\Providers\AiServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Providers\SearchServiceProvider;

return [
    AppServiceProvider::class,
    AiServiceProvider::class,
    RepositoryServiceProvider::class,
    SearchServiceProvider::class,
];
