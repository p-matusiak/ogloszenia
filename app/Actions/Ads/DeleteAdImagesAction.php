<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Models\Ad;
use Illuminate\Support\Facades\Storage;

final class DeleteAdImagesAction
{
    /**
     * Removes the given images and closes the gaps in `position`, so the ad
     * always keeps exactly one image at position 0 and no holes in between.
     *
     * @param  list<int>  $imageIds
     */
    public function execute(Ad $ad, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }

        $images = $ad->images()->whereKey($imageIds)->get();

        foreach ($images as $image) {
            Storage::disk($image->disk)->delete($image->path);
            $image->delete();
        }

        $this->reindexPositions($ad);
    }

    private function reindexPositions(Ad $ad): void
    {
        $remaining = $ad->images()->orderBy('position')->get();

        // Shift into a range that cannot collide with the final positions,
        // because (ad_id, position) is unique and Postgres checks per row.
        foreach ($remaining as $offset => $image) {
            $image->update(['position' => $offset + 1000]);
        }

        foreach ($remaining as $offset => $image) {
            $image->update(['position' => $offset]);
        }
    }
}
