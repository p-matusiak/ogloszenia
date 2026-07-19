<?php

declare(strict_types=1);

namespace App\Http\Requests\Ads;

use App\Models\Ad;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Validator;

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

    /**
     * @return list<callable(Validator): void>
     */
    protected function after(): array
    {
        return [
            ...parent::after(),
            fn (Validator $validator) => $this->validateFinalImageCount($validator),
        ];
    }

    private function validateFinalImageCount(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $ad = $this->route('ad');

        if (! $ad instanceof Ad) {
            return;
        }

        $existingCount = $ad->images()->count();
        $removedCount = count(array_unique(array_map('intval', (array) $this->input('removed_image_ids', []))));
        $incomingCount = count($this->images()) + count($this->temporaryImages());
        $finalCount = $existingCount - $removedCount + $incomingCount;
        $maximum = Config::integer('ads.images.max_per_ad');

        if ($finalCount > $maximum) {
            $validator->errors()->add('images', "An ad may hold at most {$maximum} images.");
        }
    }
}
