<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Ad;
use App\Support\PhoneMasker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ad
 */
final class AdResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price === null ? null : (float) $this->price,
            'is_negotiable' => $this->is_negotiable,
            'condition' => $this->condition?->value,
            'delivery_methods' => $this->delivery_methods,
            'delivery_prices' => (object) $this->delivery_prices,
            'location' => $this->location,
            'latitude' => $this->latitude === null ? null : (float) $this->latitude,
            'longitude' => $this->longitude === null ? null : (float) $this->longitude,
            'status' => $this->status->value,
            'rejection_reason' => $this->rejection_reason,
            // Pełny numer nigdy nie jedzie w payloadzie ogłoszenia — oddaje go
            // limitowany endpoint /ads/{ad}/phone. Wyjątkiem jest autor
            // i administrator, którym nadpisanie numeru jest potrzebne w edycji.
            'has_phone' => $this->resolvedContactPhone() !== null,
            'contact_phone_masked' => (new PhoneMasker)->mask($this->resolvedContactPhone()),
            'contact_phone' => $this->when($this->canManage($request), $this->contact_phone),
            'uses_profile_phone' => $this->contact_phone === null && $this->resolvedContactPhone() !== null,

            'views_count' => $this->views_count,
            'is_own' => $request->user()?->id === $this->user_id,
            'is_refreshable' => $this->isRefreshable(),
            'published_at' => $this->published_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'seller' => new SellerResource($this->whenLoaded('user')),
            'images' => AdImageResource::collection($this->whenLoaded('images')),
        ];
    }

    private function canManage(Request $request): bool
    {
        return $request->user()?->can('update', $this->resource) === true;
    }
}
