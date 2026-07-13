<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Publiczna wizytówka ogłoszeniodawcy. Świadomie bez adresu e-mail — kontakt
 * odbywa się przez dane podane w samym ogłoszeniu, nie przez konto.
 *
 * @mixin User
 */
final class SellerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'avatar_url' => $this->avatarUrl(),
            'member_since' => $this->created_at?->year,
        ];
    }
}
