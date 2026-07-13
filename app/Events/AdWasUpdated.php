<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Ad;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Aktywne ogłoznienie zostało zmienione w sposób istotny dla obserwujących
 * (zmiana tytułu, opisu lub ceny). Fakt, który już zaszedł.
 */
final class AdWasUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<string>  $changedAttributes  nazwy zmienionych pól istotnych dla obserwujących
     */
    public function __construct(
        public Ad $ad,
        public array $changedAttributes,
    ) {}
}
