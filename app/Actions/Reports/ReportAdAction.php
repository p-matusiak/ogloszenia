<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Models\Ad;
use App\Models\AdReport;
use App\Models\User;

final class ReportAdAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Ad $ad, array $data, ?User $reporter = null): AdReport
    {
        return AdReport::query()->create([
            'ad_id' => $ad->id,
            'reporter_id' => $reporter?->id,
            'reason' => $data['reason'],
            'message' => $data['message'] ?? null,
            'status' => ReportStatus::Pending,
        ]);
    }
}
