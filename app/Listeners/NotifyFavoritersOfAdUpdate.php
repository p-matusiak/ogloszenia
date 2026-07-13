<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AdWasUpdated;
use App\Notifications\FavoritedAdChanged;
use App\Repositories\Contracts\FavoriteRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Powiadamia obserwujących o zmianie ogłoszenia. Kolejkowany, bo fan-out maila
 * nie może blokować odpowiedzi na edycję ogłoszenia.
 */
final class NotifyFavoritersOfAdUpdate implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly FavoriteRepository $favorites) {}

    public function handle(AdWasUpdated $event): void
    {
        $recipients = $this->favorites->usersFavoriting($event->ad);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new FavoritedAdChanged($event->ad, $event->changedAttributes),
        );
    }
}
