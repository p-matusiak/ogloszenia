<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Ad;
use App\Support\AdDeletionSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

final class AdDeletionWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Ad $ad)
    {
        $this->onQueue('notifications');
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $schedule = app(AdDeletionSchedule::class);
        $daysLeft = $schedule->deletionWarningDays();

        return (new MailMessage)
            ->subject('Ogłoszenie zostanie usunięte za '.$daysLeft.' dni')
            ->greeting("Cześć {$notifiable->name}!")
            ->line("Wygasłe ogłoszenie „{$this->ad->title}” nie zostało odświeżone.")
            ->line("Za {$daysLeft} dni zostanie trwale usunięte z Twojego konta, jeśli go nie odświeżysz.")
            ->action('Odśwież ogłoszenie', $this->myAdsUrl())
            ->line('Odświeżenie przywraca widoczność ogłoszenia na kolejne 30 dni.');
    }

    private function myAdsUrl(): string
    {
        return rtrim((string) Config::string('app.url'), '/').'/moje-ogloszenia';
    }
}
