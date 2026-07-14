<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AdWasExpired;
use App\Notifications\AdExpired;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class NotifySellerOfAdExpiration implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AdWasExpired $event): void
    {
        $owner = $event->ad->loadMissing('user')->user;

        $owner->notify(new AdExpired($event->ad));
    }
}
