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
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

/**
 * Replaces Laravel's built-in VerifyEmail so the copy is Polish and the link
 * lands on our own signed route rather than the framework's default name.
 */
final class VerifyEmailAddress extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $mailLocale = 'pl')
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
            ->subject(Lang::get('mail.verify_email.subject', locale: $this->mailLocale))
            ->greeting(Lang::get('mail.common.greeting_named', ['name' => $notifiable->name], $this->mailLocale))
            ->line(Lang::get('mail.verify_email.intro', locale: $this->mailLocale))
            ->action(Lang::get('mail.verify_email.action', locale: $this->mailLocale), $this->verificationUrl($notifiable))
            ->line(Lang::get('mail.common.link_expiry_minutes', ['minutes' => $minutes], $this->mailLocale))
            ->line(Lang::get('mail.verify_email.outro', locale: $this->mailLocale))
            ->salutation(Lang::get('mail.common.salutation', locale: $this->mailLocale));
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
