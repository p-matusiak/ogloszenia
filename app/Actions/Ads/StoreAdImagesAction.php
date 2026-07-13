<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Exceptions\Domain\AdImageLimitExceededException;
use App\Models\Ad;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use RuntimeException;

final class StoreAdImagesAction
{
    /**
     * Appends images after any the ad already has. The first image an ad ever
     * receives lands at position 0 and is therefore its primary image.
     *
     * @param  list<UploadedFile>  $images
     *
     * @throws AdImageLimitExceededException
     */
    public function execute(Ad $ad, array $images): void
    {
        if ($images === []) {
            return;
        }

        $existing = $ad->images()->count();
        $maximum = Config::integer('ads.images.max_per_ad');

        if ($existing + count($images) > $maximum) {
            throw new AdImageLimitExceededException($maximum, $existing);
        }

        $disk = Config::string('ads.images.disk');
        $position = $existing;

        foreach ($images as $image) {
            $ad->images()->create($this->attributes($ad, $image, $disk, $position));
            $position++;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(Ad $ad, UploadedFile $image, string $disk, int $position): array
    {
        $path = $image->store("ads/{$ad->id}", $disk);

        if (! is_string($path)) {
            throw new RuntimeException("Failed to store image for ad {$ad->id}.");
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'original_name' => $image->getClientOriginalName(),
            'size_bytes' => $image->getSize(),
            'position' => $position,
        ];
    }
}
