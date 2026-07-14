<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\DemoMarketplaceImageStore;
use Illuminate\Console\Command;

final class FetchDemoMarketplaceImagesCommand extends Command
{
    protected $signature = 'demo:fetch-images {--force : Pobierz ponownie, nawet jeśli plik już istnieje}';

    protected $description = 'Pobiera zdjęcia produktów/usług z Wikimedia Commons do katalogu seedera demo';

    public function handle(): int
    {
        $required = DemoMarketplaceImageStore::requiredImages();
        $force = (bool) $this->option('force');

        $this->info(sprintf('Wymagane zdjęcia: %d', count($required)));

        $result = DemoMarketplaceImageStore::fetchRequiredImages(
            $force,
            function (string $status, string $imageName): void {
                match ($status) {
                    'skip' => $this->line("  = {$imageName}"),
                    'ok' => $this->info("  + {$imageName}"),
                    'fail' => $this->error("  ! {$imageName}"),
                };
            },
        );

        $this->newLine();
        $this->info(sprintf(
            'Pobrano: %d, pominięto: %d, błędy: %d',
            $result['downloaded'],
            $result['skipped'],
            $result['failed'],
        ));

        if ($result['failed'] > 0) {
            $this->warn('Uruchom ponownie z --force po poprawkach albo uzupełnij brakujące pliki ręcznie.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
