<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

final class NewConversationMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Conversation $conversation,
        private readonly Message $message,
        private readonly User $sender,
    ) {
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
        $adTitle = $this->conversation->ad->title;
        $preview = Str::limit($this->message->body, 160);

        return (new MailMessage)
            ->subject("Nowa wiadomość — {$adTitle}")
            ->greeting('Cześć!')
            ->line("{$this->sender->name} napisał(a) w sprawie ogłoszenia „{$adTitle}”.")
            ->line("„{$preview}”")
            ->action('Odpowiedz w serwisie', $this->conversationUrl())
            ->line('Jeżeli już odpowiedziałeś w aplikacji, zignoruj ten e-mail.');
    }

    private function conversationUrl(): string
    {
        return rtrim((string) Config::string('app.url'), '/')
            .'/wiadomosci/'.$this->conversation->id;
    }
}
