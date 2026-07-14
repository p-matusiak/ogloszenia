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

final class AdExpired extends Notification implements ShouldQueue
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
        $graceDays = $schedule->refreshGraceDays();
        $warningDays = $schedule->deletionWarningDays();

        return (new MailMessage)
            ->subject('Ogłoszenie wygasło — odśwież je za darmo')
            ->greeting("Cześć {$notifiable->name}!")
            ->line("Termin publikacji ogłoszenia „{$this->ad->title}” minął i nie jest już widoczne w wynikach wyszukiwania.")
            ->line("Masz {$graceDays} dni na odświeżenie — po tym czasie ogłoszenie zostanie trwale usunięte.")
            ->line('Możesz je odświeżyć jednym kliknięciem i ponownie opublikować na kolejne 30 dni.')
            ->action('Odśwież ogłoszenie', $this->myAdsUrl())
            ->line("{$warningDays} dni przed usunięciem wyślemy Ci przypomnienie e-mailem.");
    }

    private function myAdsUrl(): string
    {
        return rtrim((string) Config::string('app.url'), '/').'/moje-ogloszenia';
    }
}
