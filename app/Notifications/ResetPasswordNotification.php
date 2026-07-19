<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

final class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly string $mailLocale = 'pl',
    )
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
            ->subject(Lang::get('mail.reset_password.subject', locale: $this->mailLocale))
            ->greeting(Lang::get('mail.common.greeting_named', ['name' => $notifiable->name], $this->mailLocale))
            ->line(Lang::get('mail.reset_password.intro', locale: $this->mailLocale))
            ->action(Lang::get('mail.reset_password.action', locale: $this->mailLocale), $this->resetUrl($notifiable))
            ->line(Lang::get('mail.common.link_expiry_minutes', ['minutes' => $minutes], $this->mailLocale))
            ->line(Lang::get('mail.reset_password.outro', locale: $this->mailLocale))
            ->salutation(Lang::get('mail.common.salutation', locale: $this->mailLocale));
    }

    private function resetUrl(User $notifiable): string
    {
        return url('/reset-hasla?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]));
    }
}
