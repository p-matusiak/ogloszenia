<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Ads\WarnExpiredAdsDeletionAction;
use Illuminate\Console\Command;

final class WarnExpiredAdsDeletionCommand extends Command
{
    protected $signature = 'ads:warn-deletion';

    protected $description = 'Email owners whose expired ads will be deleted in five days';

    public function handle(WarnExpiredAdsDeletionAction $warn): int
    {
        $count = $warn->execute();

        $this->info("Queued deletion warnings for {$count} ad(s).");

        return self::SUCCESS;
    }
}
