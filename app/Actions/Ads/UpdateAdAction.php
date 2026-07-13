<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Enums\AdStatus;
use App\Enums\SettingKey;
use App\Events\AdWasUpdated;
use App\Models\Ad;
use App\Repositories\Contracts\AdRepository;
use App\Services\Contracts\SettingsRepository;
use App\Support\AdContactAttributes;
use App\Support\AdPublicationWindow;
use App\Support\AdSlugGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAdAction
{
    public function __construct(
        private AdRepository $ads,
        private SettingsRepository $settings,
        private AdSlugGenerator $slugGenerator,
        private AdImageSynchroniser $images,
        private AdPublicationWindow $window,
    ) {}

    /**
     * Zmiana tych pól jest istotna dla obserwujących ogłoszenie — wyzwala
     * powiadomienie e-mail o aktualizacji.
     *
     * @var list<string>
     */
    private const array NOTIFIABLE_ATTRIBUTES = ['title', 'description', 'price'];

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $newImages
     */
    public function execute(Ad $ad, array $data, array $newImages = []): Ad
    {
        $changed = [];

        $updated = DB::transaction(function () use ($ad, $data, $newImages, &$changed): Ad {
            /** @var list<int> $removedImageIds */
            $removedImageIds = $data['removed_image_ids'] ?? [];

            $previousSlug = $ad->slug;

            $ad->fill($this->attributes($ad, $data));
            $this->ads->save($ad);

            $changed = $this->changedNotifiableAttributes($ad);

            $this->archivePreviousSlug($ad, $previousSlug);

            $this->images->synchronise($ad, $removedImageIds, $newImages);

            return $ad->refresh();
        });

        $this->announceChange($updated, $changed);

        return $updated;
    }

    /**
     * @return list<string>
     */
    private function changedNotifiableAttributes(Ad $ad): array
    {
        return array_values(
            array_intersect(self::NOTIFIABLE_ATTRIBUTES, array_keys($ad->getChanges())),
        );
    }

    /**
     * Obserwujący widzą tylko aktywne ogłoszenia, więc tylko taka zmiana ma
     * odbiorców. Event leci po commicie — kolejkowany listener nie może odczytać
     * jeszcze niezapisanego stanu.
     *
     * @param  list<string>  $changed
     */
    private function announceChange(Ad $ad, array $changed): void
    {
        if ($changed === [] || ! $ad->isPubliclyVisible()) {
            return;
        }

        event(new AdWasUpdated($ad, $changed));
    }

    /**
     * Stary adres musi dalej prowadzić do ogłoszenia — `AdPageController` oddaje
     * z niego 301. Gdy autor cofa tytuł do brzmienia sprzed edycji, nowy slug
     * odzyskuje swój wpis z historii, bo inaczej zderzyłby się z indeksem unique.
     */
    private function archivePreviousSlug(Ad $ad, string $previousSlug): void
    {
        if ($ad->slug === $previousSlug) {
            return;
        }

        $ad->slugHistories()->where('slug', $ad->slug)->delete();
        $ad->slugHistories()->create(['slug' => $previousSlug]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(Ad $ad, array $data): array
    {
        $title = (string) $data['title'];
        $location = $data['location'] ?? null;

        $attributes = [
            'category_id' => $data['category_id'],
            'title' => $title,
            'description' => $data['description'],
            'price' => $data['price'] ?? null,
            'is_negotiable' => $data['is_negotiable'] ?? false,
            'condition' => $data['condition'] ?? null,
            'delivery_methods' => $data['delivery_methods'] ?? [],
            'delivery_prices' => $this->pricesForChosenMethods($data),
            'location' => $location,
            'district' => $data['district'] ?? null,
            'contact_email' => null,
            'contact_phone' => AdContactAttributes::overridePhone($data),
        ];

        if ($ad->title !== $title || $ad->location !== $location) {
            $attributes['slug'] = $this->slugGenerator->generate(
                $title,
                is_string($location) ? $location : null,
                $ad->id,
            );
        }

        return $attributes + $this->resubmissionAttributes($ad);
    }

    /**
     * Puste pole ceny dostawy znaczy „nie podano”, a nie „za darmo”.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function pricesForChosenMethods(array $data): array
    {
        /** @var array<string, mixed> $prices */
        $prices = $data['delivery_prices'] ?? [];

        return array_map(
            static fn (mixed $price): string => (string) $price,
            array_filter($prices, static fn (mixed $price): bool => $price !== null && $price !== ''),
        );
    }

    /**
     * Editing a rejected ad is the only way to fix it, so an edit puts it back
     * into the normal publication flow instead of leaving it stuck.
     *
     * @return array<string, mixed>
     */
    private function resubmissionAttributes(Ad $ad): array
    {
        if ($ad->status !== AdStatus::Rejected) {
            return [];
        }

        $autoApprove = $this->settings->isEnabled(SettingKey::AutoApproveAds);

        return [
            'status' => $autoApprove ? AdStatus::Active : AdStatus::Pending,
            'rejection_reason' => null,
        ] + ($autoApprove ? $this->window->open() : $this->window->closed());
    }
}
