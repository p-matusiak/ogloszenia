<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * Replaces Laravel's built-in VerifyEmail so the copy is Polish and the link
 * lands on our own signed route rather than the framework's default name.
 */
final class VerifyEmailAddress extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        // The compose stack runs a worker on this queue; heavy jobs must never
        // delay an activation mail the user is waiting for.
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
        $minutes = Config::integer('auth.verification.expire');

        return (new MailMessage)
            ->subject('Potwierdź swój adres e-mail')
            ->greeting("Cześć {$notifiable->name}!")
            ->line('Dziękujemy za założenie konta. Zanim opublikujesz pierwsze ogłoszenie, potwierdź swój adres e-mail.')
            ->action('Potwierdź adres e-mail', $this->verificationUrl($notifiable))
            ->line("Link jest ważny przez {$minutes} minut.")
            ->line('Jeżeli to nie Ty zakładałeś konto, zignoruj tę wiadomość — nic się nie stanie.')
            ->salutation('Pozdrawiamy');
    }

    /**
     * The hash pins the link to the address it was sent to: changing the email
     * after the mail went out invalidates every link already in the inbox.
     */
    private function verificationUrl(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::integer('auth.verification.expire')),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}
