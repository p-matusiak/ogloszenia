<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Publiczny profil sprzedawcy na stronie /sprzedawca/{slug}.
 *
 * @mixin User
 */
final class SellerProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'avatar_url' => $this->avatarUrl(),
            'bio' => $this->bio,
            'member_since' => $this->created_at?->year,
            'active_ads_count' => $this->whenCounted('activeAds'),
        ];
    }
}
