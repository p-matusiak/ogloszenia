<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Enums\AdStatus;
use App\Enums\SettingKey;
use App\Models\Ad;
use App\Services\Contracts\SettingsRepository;
use App\Support\AdPublicationWindow;
use App\Support\AdSlugGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAdAction
{
    public function __construct(
        private SettingsRepository $settings,
        private AdSlugGenerator $slugGenerator,
        private AdImageSynchroniser $images,
        private AdPublicationWindow $window,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $newImages
     */
    public function execute(Ad $ad, array $data, array $newImages = []): Ad
    {
        return DB::transaction(function () use ($ad, $data, $newImages): Ad {
            /** @var list<int> $removedImageIds */
            $removedImageIds = $data['removed_image_ids'] ?? [];

            $previousSlug = $ad->slug;

            $ad->fill($this->attributes($ad, $data));
            $ad->save();

            $this->archivePreviousSlug($ad, $previousSlug);

            $this->images->synchronise($ad, $removedImageIds, $newImages);

            return $ad->refresh();
        });
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
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
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
