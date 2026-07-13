<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\AdRepository;
use App\Repositories\Eloquent\EloquentAdRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdRepository::class, EloquentAdRepository::class);
    }
}
