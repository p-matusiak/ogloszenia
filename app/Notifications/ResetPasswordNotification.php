<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

final class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $token)
    {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $minutes = Config::integer('auth.passwords.'.Config::string('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset hasła')
            ->greeting("Cześć {$notifiable->name}!")
            ->line('Otrzymaliśmy prośbę o ustawienie nowego hasła do Twojego konta.')
            ->action('Ustaw nowe hasło', $this->resetUrl($notifiable))
            ->line("Link jest ważny przez {$minutes} minut.")
            ->line('Jeżeli to nie Ty wysłałeś tę prośbę, zignoruj tę wiadomość. Twoje hasło pozostanie bez zmian.')
            ->salutation('Pozdrawiamy');
    }

    private function resetUrl(User $notifiable): string
    {
        return url('/reset-hasla?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]));
    }
}
