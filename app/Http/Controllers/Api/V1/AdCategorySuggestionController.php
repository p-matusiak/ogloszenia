<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Ads\SuggestAdCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\SuggestAdCategoryRequest;
use Illuminate\Http\JsonResponse;

final class AdCategorySuggestionController extends Controller
{
    public function __invoke(SuggestAdCategoryRequest $request, SuggestAdCategoryAction $suggest): JsonResponse
    {
        return response()->json([
            'data' => $suggest->execute((string) $request->validated('title')),
        ]);
    }
}
