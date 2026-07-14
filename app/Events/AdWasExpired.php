<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Ad;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Termin publikacji minął — ogłoszenie zniknęło z listingu. */
final class AdWasExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(public Ad $ad) {}
}
