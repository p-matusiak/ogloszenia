<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'position' => $this->position,
            'is_visible' => $this->is_visible,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            // Nearest ancestor first, so the UI renders "Motoryzacja > Samochody".
            'ancestors' => CategoryResource::collection($this->whenLoaded('ancestors')),
        ];
    }
}
