<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;
use App\Models\User;
use App\Support\SellerSlugGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Realistyczne ogłoszenia demo w każdej kategorii liściowej — do wypełnienia portalu
 * treścią wyglądającą na prawdziwą (bez numerów telefonu).
 *
 * Brakujące zdjęcia pobiera automatycznie z Wikimedia Commons.
 */
final class DemoMarketplaceSeeder extends Seeder
{
    /**
     * @var array<string, array{absolute: string, original_name: string, size_bytes: int}>
     */
    private array $imageCache = [];

    public function run(): void
    {
        $this->ensureImagesAreReady();
        $this->clearDemoMarketplace();

        $sellerCount = max(10, (int) config('seeding.demo_seller_count', 120));
        $users = $this->seedSellers($sellerCount);
        $categories = $this->targetCategories();

        if ($categories->isEmpty()) {
            if ($this->command !== null) {
                $this->command->warn('DemoMarketplaceSeeder: brak kategorii liściowych.');
            }

            return;
        }

        $perCategory = max(1, (int) config('seeding.demo_ads_per_category', 100));
        $batchSize = max(1, (int) config('seeding.demo_batch_size', 50));
        $now = now();
        $globalIndex = 0;

        foreach ($categories as $category) {
            $adBatch = [];
            $imageMeta = [];

            for ($index = 1; $index <= $perCategory; $index++) {
                $globalIndex++;
                $listing = DemoMarketplaceCatalog::listing($category->slug, $index);
                $seller = $users[($globalIndex - 1) % $users->count()];
                $place = DemoMarketplaceCatalog::place($globalIndex);
                $slug = DemoMarketplaceCatalog::adSlug($category->slug, $index, $listing['title']);

                $adBatch[] = [
                    'user_id' => $seller->id,
                    'category_id' => $category->id,
                    'title' => $listing['title'],
                    'slug' => $slug,
                    'description' => $listing['description'],
                    'price' => $listing['price'],
                    'is_negotiable' => $listing['is_negotiable'],
                    'condition' => $listing['condition'],
                    'delivery_methods' => json_encode($listing['delivery_methods'], JSON_THROW_ON_ERROR),
                    'delivery_prices' => json_encode($listing['delivery_prices'], JSON_THROW_ON_ERROR),
                    'location' => $place['location'],
                    'latitude' => $place['latitude'],
                    'longitude' => $place['longitude'],
                    'contact_email' => null,
                    'contact_phone' => null,
                    'status' => AdStatus::Active->value,
                    'rejection_reason' => null,
                    'published_at' => $now->copy()->subDays($globalIndex % 60)->subHours($globalIndex % 24),
                    'expires_at' => $now->copy()->addDays(25 + ($globalIndex % 10)),
                    'terms_accepted_at' => $now,
                    'views_count' => 15 + ($globalIndex % 900),
                    'phone_reveals_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $imageMeta[] = [
                    'slug' => $slug,
                    'image_name' => $listing['image_name'],
                ];

                if (count($adBatch) < $batchSize) {
                    continue;
                }

                $this->persistBatch($adBatch, $imageMeta, $now);
                $adBatch = [];
                $imageMeta = [];
            }

            if ($adBatch !== []) {
                $this->persistBatch($adBatch, $imageMeta, $now);
            }

            if ($this->command !== null) {
                $this->command->info(sprintf(
                    'DemoMarketplaceSeeder: %s — %d ogłoszeń',
                    $category->slug,
                    $perCategory,
                ));
            }
        }
    }

    private function ensureImagesAreReady(): void
    {
        if (! config('seeding.demo_fetch_images', true)) {
            $this->assertAllImagesExist();

            return;
        }

        $requiredCount = count(DemoMarketplaceImageStore::requiredImages());
        $missingBefore = $this->missingImageCount();

        if ($missingBefore === 0) {
            return;
        }

        if ($this->command !== null) {
            $this->command->info(sprintf(
                'DemoMarketplaceSeeder: pobieram %d/%d zdjęć z Wikimedia Commons…',
                $missingBefore,
                $requiredCount,
            ));
        }

        $result = DemoMarketplaceImageStore::fetchRequiredImages(
            onProgress: function (string $status, string $imageName): void {
                if ($this->command === null || $status !== 'ok') {
                    return;
                }

                $this->command->line("  + {$imageName}");
            },
        );

        if ($this->command !== null && $result['downloaded'] > 0) {
            $this->command->info(sprintf(
                'DemoMarketplaceSeeder: pobrano %d zdjęć (pominięto: %d, błędy: %d)',
                $result['downloaded'],
                $result['skipped'],
                $result['failed'],
            ));
        }

        $this->assertAllImagesExist();
    }

    private function missingImageCount(): int
    {
        $missing = 0;

        foreach (DemoMarketplaceImageStore::requiredImages() as $imageName => $_meta) {
            if (! DemoMarketplaceImageStore::assetExists($imageName)) {
                $missing++;
            }
        }

        return $missing;
    }

    private function assertAllImagesExist(): void
    {
        $missing = [];

        foreach (DemoMarketplaceImageStore::requiredImages() as $imageName => $_meta) {
            if (! DemoMarketplaceImageStore::assetExists($imageName)) {
                $missing[] = $imageName;
            }
        }

        if ($missing === []) {
            return;
        }

        $preview = implode(', ', array_slice($missing, 0, 5));
        $suffix = count($missing) > 5 ? '…' : '';

        throw new \RuntimeException(sprintf(
            'Brakuje %d zdjęć demo (%s%s). Sprawdź połączenie z internetem i uruchom: php artisan demo:fetch-images',
            count($missing),
            $preview,
            $suffix,
        ));
    }

    private function clearDemoMarketplace(): void
    {
        Ad::query()
            ->where('slug', 'like', DemoMarketplaceCatalog::SLUG_PREFIX.'-%')
            ->delete();
    }

    /**
     * @return Collection<int, User>
     */
    private function seedSellers(int $count): Collection
    {
        $slugGenerator = app(SellerSlugGenerator::class);

        for ($sequence = 1; $sequence <= $count; $sequence++) {
            $profile = DemoMarketplaceCatalog::seller($sequence);

            $attributes = [
                'name' => $profile['name'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'avatar_path' => null,
                'bio' => $profile['bio'],
                'phone' => null,
                'is_admin' => false,
            ];

            if (! User::query()->where('email', $profile['email'])->exists()) {
                $attributes['slug'] = $slugGenerator->generate($profile['name']);
            }

            User::query()->updateOrCreate(['email' => $profile['email']], $attributes);
        }

        return User::query()
            ->where('email', 'like', DemoMarketplaceCatalog::SELLER_EMAIL_PREFIX.'%')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    private function targetCategories(): Collection
    {
        $canonical = DemoMarketplaceCatalog::canonicalCategorySlugs();

        return Category::query()
            ->whereDoesntHave('children')
            ->whereIn('slug', $canonical)
            ->orderBy('position')
            ->get();
    }

    /**
     * @param  array<int, array<string, mixed>>  $adBatch
     * @param  array<int, array{slug: string, image_name: string}>  $imageMeta
     */
    private function persistBatch(array $adBatch, array $imageMeta, Carbon $now): void
    {
        Ad::query()->insert($adBatch);

        $idsBySlug = Ad::query()
            ->whereIn('slug', array_column($imageMeta, 'slug'))
            ->pluck('id', 'slug')
            ->all();

        $imageRows = [];

        foreach ($imageMeta as $meta) {
            $adId = $idsBySlug[$meta['slug']] ?? null;

            if ($adId === null) {
                continue;
            }

            $fixture = $this->resolveImage($meta['image_name'], (int) $adId);

            $imageRows[] = [
                'ad_id' => $adId,
                'disk' => 'public',
                'path' => $fixture['path'],
                'original_name' => $fixture['original_name'],
                'size_bytes' => $fixture['size_bytes'],
                'position' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($imageRows !== []) {
            AdImage::query()->insert($imageRows);
        }
    }

    /**
     * @return array{path: string, original_name: string, size_bytes: int}
     */
    private function resolveImage(string $imageName, int $adId): array
    {
        $normalized = DemoMarketplaceImageStore::normalizeImageName($imageName);

        if (isset($this->imageCache[$normalized])) {
            return $this->copyAssetToAd($this->imageCache[$normalized], $adId, $normalized);
        }

        $absolute = DemoMarketplaceImageStore::assetAbsolutePath($normalized);

        if (! is_file($absolute)) {
            throw new \RuntimeException("Brak zdjęcia demo: {$normalized}");
        }

        $cached = [
            'absolute' => $absolute,
            'original_name' => $normalized,
            'size_bytes' => max(0, (int) filesize($absolute)),
        ];

        $this->imageCache[$normalized] = $cached;

        return $this->copyAssetToAd($cached, $adId, $normalized);
    }

    /**
     * @param  array{absolute: string, original_name: string, size_bytes: int}  $asset
     * @return array{path: string, original_name: string, size_bytes: int}
     */
    private function copyAssetToAd(array $asset, int $adId, string $imageName): array
    {
        $extension = pathinfo($imageName, PATHINFO_EXTENSION) ?: 'jpg';
        $targetPath = sprintf(
            'ads/demo/%d/%s',
            $adId,
            Str::slug(pathinfo($imageName, PATHINFO_FILENAME)).'.'.$extension,
        );

        if (! Storage::disk('public')->exists($targetPath)) {
            Storage::disk('public')->makeDirectory('ads/demo/'.$adId);
            Storage::disk('public')->put($targetPath, (string) file_get_contents($asset['absolute']));
        }

        $absolute = Storage::disk('public')->path($targetPath);

        return [
            'path' => $targetPath,
            'original_name' => $imageName,
            'size_bytes' => max(0, (int) filesize($absolute)),
        ];
    }
}
