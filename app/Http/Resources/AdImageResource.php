<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AdImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AdImage
 */
final class AdImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url(),
            'position' => $this->position,
            'is_primary' => $this->isPrimary(),
        ];
    }
}
