<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdReportRequest;
use App\Http\Resources\AdReportResource;
use App\Models\AdReport;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Config;

final class AdReportsController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $reports = AdReport::query()
            ->with(['ad', 'reporter'])
            ->pending()
            ->latest('created_at')
            ->paginate(Config::integer('ads.per_page'));

        return AdReportResource::collection($reports);
    }

    public function update(UpdateAdReportRequest $request, AdReport $report): AdReportResource
    {
        $report->update($request->validated());

        return new AdReportResource($report->load(['ad', 'reporter']));
    }
}
