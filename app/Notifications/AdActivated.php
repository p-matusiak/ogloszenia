<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

final class AdActivated extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Twoje ogłoszenie jest już widoczne')
            ->greeting("Cześć {$notifiable->name}!")
            ->line("Ogłoszenie „{$this->ad->title}” zostało opublikowane i jest widoczne w serwisie.")
            ->action('Zobacz ogłoszenie', $this->adUrl())
            ->line('Powodzenia w sprzedaży!');
    }

    private function adUrl(): string
    {
        return rtrim((string) Config::string('app.url'), '/').'/ogloszenie/'.$this->ad->slug;
    }
}
