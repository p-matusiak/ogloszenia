<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AdCategorySuggestionController;
use App\Http\Controllers\Api\V1\AdMessagesController;
use App\Http\Controllers\Api\V1\Admin\AdModerationController;
use App\Http\Controllers\Api\V1\Admin\AdReportsController as AdminAdReportsController;
use App\Http\Controllers\Api\V1\Admin\AdsController as AdminAdsController;
use App\Http\Controllers\Api\V1\Admin\CategoriesController as AdminCategoriesController;
use App\Http\Controllers\Api\V1\Admin\SettingsController;
use App\Http\Controllers\Api\V1\AdMoreFromSellerController;
use App\Http\Controllers\Api\V1\AdPhoneController;
use App\Http\Controllers\Api\V1\AdRefreshController;
use App\Http\Controllers\Api\V1\AdReportsController;
use App\Http\Controllers\Api\V1\AdsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoriesController;
use App\Http\Controllers\Api\V1\ConversationsController;
use App\Http\Controllers\Api\V1\EmailVerificationNotificationController;
use App\Http\Controllers\Api\V1\FavoritesController;
use App\Http\Controllers\Api\V1\MyAdsController;
use App\Http\Controllers\Api\V1\OAuthProvidersController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\TemporaryAdImagesController;
use App\Http\Controllers\Api\V1\SellersController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('categories', CategoriesController::class);

    Route::get('ads', [AdsController::class, 'index']);
    Route::get('ads/{ad}/more-from-seller', AdMoreFromSellerController::class);
    Route::get('ads/{ad}', [AdsController::class, 'show']);
    Route::get('sellers/{seller}', [SellersController::class, 'show'])
        ->where('seller', '[a-z0-9-]+');

    // Guests may report ads, so this sits outside the auth group. Throttled
    // tightly: it is the only write endpoint an anonymous visitor can reach.
    Route::post('ads/{ad}/reports', AdReportsController::class)->middleware('throttle:10,60');

    // Pełny numer telefonu wyłącznie na jawne żądanie i pod ostrym limitem —
    // inaczej jedno przejście po liście oddałoby scraperowi wszystkie numery.
    Route::post('ads/{ad}/phone', AdPhoneController::class)->middleware('throttle:15,60');

    Route::get('auth/oauth-providers', OAuthProvidersController::class);

    Route::middleware('guest')->group(function (): void {
        Route::post('auth/register', [AuthController::class, 'register'])
            ->middleware(app()->isProduction() ? 'throttle:5,60' : 'throttle:30,1');
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/forgot-password', [PasswordResetController::class, 'sendLink']);
        Route::post('auth/reset-password', [PasswordResetController::class, 'reset']);
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/profile', [AuthController::class, 'updateProfile']);
        Route::delete('auth/account', [AuthController::class, 'deleteAccount']);

        // Throttled per user: resending is the only way to make the app send
        // mail on demand, so it is the obvious lever for an outbound spam relay.
        Route::post('auth/email/verification-notification', EmailVerificationNotificationController::class)
            ->middleware('throttle:6,1');

        Route::get('my/ads', MyAdsController::class);

        // Ulubione: obserwowanie ogłoszeń nie wymaga zweryfikowanego adresu —
        // to akcja czytelnika, nie publikacja treści. Lista i identyfikatory
        // (do serduszek na froncie) obok dodawania/usuwania.
        Route::get('my/favorites', [FavoritesController::class, 'index']);
        Route::get('my/favorites/ids', [FavoritesController::class, 'ids']);
        Route::post('ads/{ad}/favorite', [FavoritesController::class, 'store']);
        Route::delete('ads/{ad}/favorite', [FavoritesController::class, 'destroy']);

        // Wiadomości między kupującym a sprzedającym — tylko zalogowani.
        Route::get('my/conversations', [ConversationsController::class, 'index']);
        Route::get('my/conversations/unread-count', [ConversationsController::class, 'unreadCount']);
        Route::get('conversations/{conversation}', [ConversationsController::class, 'show']);
        Route::get('conversations/{conversation}/messages', [ConversationsController::class, 'messages']);
        Route::post('conversations/{conversation}/messages', [ConversationsController::class, 'reply'])
            ->middleware('throttle:30,1');
        Route::post('ads/{ad}/messages', [AdMessagesController::class, 'store'])
            ->middleware('throttle:15,1');

        // Publishing is gated on a confirmed address: an unverified account
        // must not be able to put contact details in front of visitors. Reading
        // and deleting stay open, so nobody is locked out of their own data.
        Route::middleware('verified')->group(function (): void {
            Route::post('ads/temp-images', [TemporaryAdImagesController::class, 'store']);
            Route::delete('ads/temp-images/{token}', [TemporaryAdImagesController::class, 'destroy']);
            Route::post('ads/suggest-category', AdCategorySuggestionController::class)
                ->middleware('throttle:30,1');
            Route::post('ads', [AdsController::class, 'store']);
            // POST rather than PUT: the edit form is multipart, and browsers cannot
            // send multipart bodies with PUT without method spoofing.
            Route::post('ads/{ad}', [AdsController::class, 'update']);
            Route::post('ads/{ad}/refresh', AdRefreshController::class);
        });

        Route::delete('ads/{ad}', [AdsController::class, 'destroy']);

        Route::prefix('admin')->middleware('admin')->group(function (): void {
            Route::get('ads', [AdminAdsController::class, 'index']);
            Route::get('ads/{ad}', [AdminAdsController::class, 'show']);
            Route::delete('ads/{ad}', [AdminAdsController::class, 'destroy']);
            Route::post('ads/{ad}/approve', [AdModerationController::class, 'approve']);
            Route::post('ads/{ad}/reject', [AdModerationController::class, 'reject']);

            Route::get('categories', [AdminCategoriesController::class, 'index']);
            Route::post('categories', [AdminCategoriesController::class, 'store']);
            Route::put('categories/{category}', [AdminCategoriesController::class, 'update']);
            Route::delete('categories/{category}', [AdminCategoriesController::class, 'destroy']);

            Route::get('reports', [AdminAdReportsController::class, 'index']);
            Route::put('reports/{report}', [AdminAdReportsController::class, 'update']);

            Route::get('settings', [SettingsController::class, 'show']);
            Route::put('settings', [SettingsController::class, 'update']);
        });
    });
});
