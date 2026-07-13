<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AdReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AdReport
 */
final class AdReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'message' => $this->message,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'ad' => new AdSummaryResource($this->whenLoaded('ad')),
            'reporter' => new UserResource($this->whenLoaded('reporter')),
        ];
    }
}
