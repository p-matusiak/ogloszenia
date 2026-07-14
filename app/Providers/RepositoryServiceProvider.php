<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\AdRepository;
use App\Repositories\Contracts\CategoryRepository;
use App\Repositories\Contracts\ConversationRepository;
use App\Repositories\Contracts\FavoriteRepository;
use App\Repositories\Contracts\MessageRepository;
use App\Repositories\Contracts\OAuthAccountRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Eloquent\EloquentAdRepository;
use App\Repositories\Eloquent\EloquentCategoryRepository;
use App\Repositories\Eloquent\EloquentConversationRepository;
use App\Repositories\Eloquent\EloquentFavoriteRepository;
use App\Repositories\Eloquent\EloquentMessageRepository;
use App\Repositories\Eloquent\EloquentOAuthAccountRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdRepository::class, EloquentAdRepository::class);
        $this->app->bind(CategoryRepository::class, EloquentCategoryRepository::class);
        $this->app->bind(ConversationRepository::class, EloquentConversationRepository::class);
        $this->app->bind(FavoriteRepository::class, EloquentFavoriteRepository::class);
        $this->app->bind(MessageRepository::class, EloquentMessageRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(OAuthAccountRepository::class, EloquentOAuthAccountRepository::class);
    }
}
