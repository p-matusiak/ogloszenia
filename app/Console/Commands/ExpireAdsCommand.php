<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Ads\ExpireAdsAction;
use Illuminate\Console\Command;

final class ExpireAdsCommand extends Command
{
    protected $signature = 'ads:expire';

    protected $description = 'Move active ads past their expiry date into the expired status';

    public function handle(ExpireAdsAction $expireAds): int
    {
        $count = $expireAds->execute();

        $this->info("Expired {$count} ad(s).");

        return self::SUCCESS;
    }
}
