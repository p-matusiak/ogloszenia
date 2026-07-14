<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AdWasActivated;
use App\Notifications\AdActivated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class NotifySellerOfAdActivation implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AdWasActivated $event): void
    {
        $owner = $event->ad->loadMissing('user')->user;

        $owner->notify(new AdActivated($event->ad));
    }
}
