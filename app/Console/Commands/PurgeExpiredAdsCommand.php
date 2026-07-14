<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Ads\PurgeExpiredAdsAction;
use Illuminate\Console\Command;

final class PurgeExpiredAdsCommand extends Command
{
    protected $signature = 'ads:purge-expired';

    protected $description = 'Delete expired ads that were not refreshed within the grace period';

    public function handle(PurgeExpiredAdsAction $purge): int
    {
        $count = $purge->execute();

        $this->info("Purged {$count} ad(s).");

        return self::SUCCESS;
    }
}
