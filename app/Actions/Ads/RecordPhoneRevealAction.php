<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Models\Ad;

final class RecordPhoneRevealAction
{
    /**
     * Licznik odsłon numeru. Nagły skok zdradza scrapera, który przeszedł
     * przez limit zapytań, a autorowi mówi, ilu ludzi chciało zadzwonić.
     */
    public function execute(Ad $ad): void
    {
        $ad->increment('phone_reveals_count');
    }
}
