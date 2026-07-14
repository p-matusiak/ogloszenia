<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Actions\Users\SyncUserDefaultLocationAction;
use App\Enums\AdStatus;
use App\Enums\SettingKey;
use App\Events\AdWasActivated;
use App\Exceptions\Domain\DailyAdLimitReachedException;
use App\Models\Ad;
use App\Models\User;
use App\Repositories\Contracts\AdRepository;
use App\Services\Ads\AdPublicationDecisionResolver;
use App\Services\Contracts\AdContentModerator;
use App\Services\Contracts\SettingsRepository;
use App\Support\AdContactAttributes;
use App\Support\AdSlugGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

final readonly class CreateAdAction
{
    public function __construct(
        private AdRepository $ads,
        private SettingsRepository $settings,
        private AdSlugGenerator $slugGenerator,
        private StoreAdImagesAction $storeImages,
        private AdContentModerator $moderator,
        private AdPublicationDecisionResolver $publication,
        private SyncUserDefaultLocationAction $syncDefaultLocation,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $images
     *
     * @throws DailyAdLimitReachedException
     */
    public function execute(User $user, array $data, array $images = []): Ad
    {
        // Serialised per user: two simultaneous submissions must not both pass
        // the daily-limit check on the same remaining slot.
        return Cache::lock("ads:create:{$user->id}", 10)->block(5, function () use ($user, $data, $images): Ad {
            $this->guardDailyLimit($user);

            $ad = DB::transaction(function () use ($user, $data, $images): Ad {
                $ad = $this->ads->create($this->attributes($user, $data));

                $this->storeImages->execute($ad, $images);
                $this->syncDefaultLocation->execute($user, $data);

                return $ad;
            });

            if ($ad->status === AdStatus::Active) {
                event(new AdWasActivated($ad));
            }

            return $ad;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(User $user, array $data): array
    {
        $title = (string) $data['title'];
        $location = $data['location'] ?? null;
        $moderation = $this->moderator->review($title, (string) $data['description']);
        $autoApprove = $this->settings->isEnabled(SettingKey::AutoApproveAds);

        return [
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'title' => $title,
            'slug' => $this->slugGenerator->generate($title, is_string($location) ? $location : null),
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
            'terms_accepted_at' => CarbonImmutable::now(),
        ] + $this->publication->resolveForCreate($moderation, $autoApprove);
    }

    /**
     * Puste pole ceny dostawy znaczy „nie podano”, a nie „za darmo”, więc
     * nie zapisujemy go wcale.
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
     * @throws DailyAdLimitReachedException
     */
    private function guardDailyLimit(User $user): void
    {
        $limit = Config::integer('ads.daily_limit_per_user');

        $today = $this->ads->countCreatedTodayForUser($user->id);

        if ($today >= $limit) {
            throw new DailyAdLimitReachedException($limit);
        }
    }
}
