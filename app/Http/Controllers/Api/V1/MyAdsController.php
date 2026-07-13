<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdSummaryResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Config;

final class MyAdsController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        assert($user instanceof User);

        $ads = $user
            ->ads()
            ->with(['category.ancestors', 'primaryImage'])
            ->latest('created_at')
            ->paginate(Config::integer('ads.per_page'));

        return AdSummaryResource::collection($ads);
    }
}
