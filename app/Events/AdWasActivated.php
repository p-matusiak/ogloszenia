<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Ad;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Ogłoszenie weszło do publicznego obiegu — po moderacji albo auto-akceptacji. */
final class AdWasActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Ad $ad) {}
}
