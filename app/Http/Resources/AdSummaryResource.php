<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * The listing payload. Deliberately omits description, contact details and the
 * image gallery, which only the detail endpoint returns.
 *
 * @mixin Ad
 */
final class AdSummaryResource extends JsonResource
{
    private const int EXCERPT_LENGTH = 160;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => Str::limit($this->description, self::EXCERPT_LENGTH),
            'price' => $this->price === null ? null : (float) $this->price,
            'is_negotiable' => $this->is_negotiable,
            'condition' => $this->condition?->value,
            'delivery_methods' => $this->delivery_methods,
            'location' => $this->location,
            'status' => $this->status->value,
            'published_at' => $this->published_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'views_count' => $this->views_count,
            'primary_image_url' => $this->whenLoaded(
                'primaryImage',
                fn (): ?string => $this->primaryImage?->url(),
            ),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
