<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Actions\Users\SyncUserDefaultLocationAction;
use App\Enums\AdStatus;
use App\Enums\SettingKey;
use App\Events\AdWasActivated;
use App\Events\AdWasUpdated;
use App\Models\Ad;
use App\Repositories\Contracts\AdRepository;
use App\Services\Ads\AdPublicationDecisionResolver;
use App\Services\Contracts\AdContentModerator;
use App\Services\Contracts\SettingsRepository;
use App\Support\AdContactAttributes;
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
        private AdContentModerator $moderator,
        private AdPublicationDecisionResolver $publication,
        private SyncUserDefaultLocationAction $syncDefaultLocation,
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
        $wasActivated = false;

        $updated = DB::transaction(function () use ($ad, $data, $newImages, &$changed, &$wasActivated): Ad {
            /** @var list<int> $removedImageIds */
            $removedImageIds = $data['removed_image_ids'] ?? [];

            $previousSlug = $ad->slug;
            $previousStatus = $ad->status;

            $ad->fill($this->attributes($ad, $data));
            $this->ads->save($ad);

            $wasActivated = $previousStatus !== AdStatus::Active && $ad->status === AdStatus::Active;
            $changed = $this->changedNotifiableAttributes($ad);

            $this->archivePreviousSlug($ad, $previousSlug);

            $this->images->synchronise($ad, $removedImageIds, $newImages);
            $this->syncDefaultLocation->execute($ad->user, $data);

            return $ad->refresh();
        });

        if ($wasActivated) {
            event(new AdWasActivated($updated));
        }

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
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
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

        $moderation = $this->moderator->review($title, (string) $data['description']);
        $autoApprove = $this->settings->isEnabled(SettingKey::AutoApproveAds);

        return $attributes + $this->publication->resolveForUpdate($ad, $moderation, $autoApprove);
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
}
