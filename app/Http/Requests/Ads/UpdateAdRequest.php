<?php

declare(strict_types=1);

namespace App\Http\Requests\Ads;

use App\Models\Ad;

final class UpdateAdRequest extends StoreAdRequest
{
    public function authorize(): bool
    {
        $ad = $this->route('ad');

        return $ad instanceof Ad && $this->user()?->can('update', $ad) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            // Ownership is enforced in the action by scoping the delete to this
            // ad's images, so a foreign image id simply matches nothing.
            'removed_image_ids' => ['array'],
            'removed_image_ids.*' => ['integer'],
        ];
    }
}
