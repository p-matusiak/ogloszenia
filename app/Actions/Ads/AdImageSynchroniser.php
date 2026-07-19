<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Exceptions\Domain\AdImageLimitExceededException;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Http\UploadedFile;

final readonly class AdImageSynchroniser
{
    public function __construct(
        private StoreAdImagesAction $store,
        private StoreTemporaryAdImagesAction $storeTemporary,
        private DeleteAdImagesAction $delete,
    ) {}

    /**
     * Removals run first so that an edit which swaps all ten images for ten new
     * ones does not trip the per-ad image limit.
     *
     * @param  list<int>  $removedImageIds
     * @param  list<UploadedFile>  $newImages
     * @param  list<string>  $temporaryImages
     *
     * @throws AdImageLimitExceededException
     */
    public function synchronise(Ad $ad, User $user, array $removedImageIds, array $newImages, array $temporaryImages): void
    {
        $this->delete->execute($ad, $removedImageIds);

        $ad->load('images');

        $this->store->execute($ad, $newImages);
        $this->storeTemporary->execute($ad, $user, $temporaryImages);
    }
}
