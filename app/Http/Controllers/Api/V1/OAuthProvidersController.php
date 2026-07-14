<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\OAuthConfigurator;
use Illuminate\Http\JsonResponse;

final class OAuthProvidersController extends Controller
{
    public function __invoke(OAuthConfigurator $oauth): JsonResponse
    {
        return response()->json([
            'providers' => array_map(
                static fn ($provider) => $provider->value,
                $oauth->configuredProviders(),
            ),
        ]);
    }
}
