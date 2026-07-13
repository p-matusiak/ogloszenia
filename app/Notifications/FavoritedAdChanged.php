<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

final class FavoritedAdChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Etykiety pól po polsku dla treści maila. Klucze odpowiadają nazwom
     * atrybutów śledzonych przez UpdateAdAction.
     *
     * @var array<string, string>
     */
    private const array ATTRIBUTE_LABELS = [
        'title' => 'tytuł',
        'description' => 'opis',
        'price' => 'cena',
    ];

    /**
     * @param  list<string>  $changedAttributes
     */
    public function __construct(
        private readonly Ad $ad,
        private readonly array $changedAttributes,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) Config::string('app.url'), '/').'/ogloszenie/'.$this->ad->slug;

        return (new MailMessage)
            ->subject('Obserwowane ogłoszenie się zmieniło')
            ->greeting('Cześć!')
            ->line("Ogłoszenie „{$this->ad->title}”, które obserwujesz, zostało zaktualizowane.")
            ->line('Zmieniło się: '.$this->changedLabels().'.')
            ->action('Zobacz ogłoszenie', $url)
            ->line('Nie chcesz już dostawać takich wiadomości? Usuń ogłoszenie z ulubionych.');
    }

    private function changedLabels(): string
    {
        $labels = array_map(
            static fn (string $attribute): string => self::ATTRIBUTE_LABELS[$attribute] ?? $attribute,
            $this->changedAttributes,
        );

        return implode(', ', $labels);
    }
}
