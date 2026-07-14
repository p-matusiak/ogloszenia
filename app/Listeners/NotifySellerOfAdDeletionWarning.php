<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AdDeletionWarningDue;
use App\Notifications\AdDeletionWarning;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class NotifySellerOfAdDeletionWarning implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AdDeletionWarningDue $event): void
    {
        $owner = $event->ad->loadMissing('user')->user;

        $owner->notify(new AdDeletionWarning($event->ad));
    }
}
