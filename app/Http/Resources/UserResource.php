<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'bio' => $this->bio,
            'phone' => $this->phone,
            'default_location' => $this->default_location,
            'default_latitude' => $this->default_latitude === null ? null : (float) $this->default_latitude,
            'default_longitude' => $this->default_longitude === null ? null : (float) $this->default_longitude,
            'avatar_url' => $this->avatarUrl(),
            'is_admin' => $this->is_admin,
            'is_email_verified' => $this->hasVerifiedEmail(),
        ];
    }
}
