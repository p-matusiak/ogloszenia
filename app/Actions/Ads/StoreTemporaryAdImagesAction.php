<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Exceptions\Domain\AdImageLimitExceededException;
use App\Models\Ad;
use App\Models\User;
use App\Support\TemporaryAdImageStorage;
use Illuminate\Support\Facades\Config;

final readonly class StoreTemporaryAdImagesAction
{
    public function __construct(
        private TemporaryAdImageStorage $storage,
    ) {}

    /**
     * @param  list<string>  $temporaryImages
     *
     * @throws AdImageLimitExceededException
     */
    public function execute(Ad $ad, User $user, array $temporaryImages): void
    {
        if ($temporaryImages === []) {
            return;
        }

        $existing = $ad->images()->count();
        $maximum = Config::integer('ads.images.max_per_ad');

        if ($existing + count($temporaryImages) > $maximum) {
            throw new AdImageLimitExceededException($maximum, $existing);
        }

        $position = $existing;

        foreach ($temporaryImages as $token) {
            $ad->images()->create($this->storage->moveToAd($user, $token, $ad, $position));
            $position++;
        }
    }
}
