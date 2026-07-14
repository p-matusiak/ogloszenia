<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Ad;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AdDeletionWarningDue
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Ad $ad) {}
}
