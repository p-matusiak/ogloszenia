<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Reports\ReportAdAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ads\ReportAdRequest;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AdReportsController extends Controller
{
    public function __invoke(ReportAdRequest $request, Ad $ad, ReportAdAction $reportAd): JsonResponse
    {
        $reportAd->execute($ad, $request->validated(), $request->user());

        return response()->json([], Response::HTTP_ACCEPTED);
    }
}
