<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;
use App\Models\User;
use App\Support\SellerSlugGenerator;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

final class AdSeeder extends Seeder
{
    private const string SLUG_PREFIX = 'seed-ads';

    /**
     * @var list<array{path: string, original_name: string}>
     */
    private const array IMAGE_PATHS = [
        ['path' => 'ads/3/laptop.jpg', 'original_name' => 'laptop.jpg'],
        ['path' => 'ads/3/5tQGXtFYY5jyz7A4WEioyfrOxMVdqR7COVD6tKvE.png', 'original_name' => 'rower-miejski.png'],
        ['path' => 'ads/3/ULdbLzVOHjXyNOeyJ1RUi1w3o83TZvjUkxx8Lz3D.png', 'original_name' => 'fotel.png'],
    ];

    public function run(): void
    {
        $this->clearSeedAds();
        $users = $this->seedSellers();
        $categories = $this->leafCategories();
        $fixtures = $this->imageFixtures();

        $this->seedAds($users, $categories, $fixtures);
    }

    private function clearSeedAds(): void
    {
        Ad::query()
            ->where('slug', 'like', self::SLUG_PREFIX.'-%')
            ->delete();
    }

    /**
     * @return Collection<int, User>
     */
    private function seedSellers(): Collection
    {
        $count = max(1, (int) config('seeding.seller_count', 250));

        $slugGenerator = app(SellerSlugGenerator::class);

        for ($i = 1; $i <= $count; $i++) {
            $name = AdSeederProfile::sellerName($i);
            $email = AdSeederProfile::sellerEmail($i);

            $attributes = [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'avatar_path' => null,
                'bio' => null,
                'phone' => $i % 2 === 0
                    ? sprintf('+48 501 %03d %03d', $i % 1_000, ($i * 3) % 1_000)
                    : null,
                'is_admin' => false,
            ];

            if (! User::query()->where('email', $email)->exists()) {
                $attributes['slug'] = $slugGenerator->generate($name);
            }

            User::query()->updateOrCreate(['email' => $email], $attributes);
        }

        return User::query()
            ->where('email', 'like', 'seed-seller-%@zunto.local')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    private function leafCategories(): Collection
    {
        return Category::query()
            ->with('ancestors')
            ->whereDoesntHave('children')
            ->orderBy('position')
            ->get();
    }

    /**
     * @return list<array{path: string, original_name: string, size_bytes: int}>
     */
    private function imageFixtures(): array
    {
        return array_map(
            static function (array $fixture): array {
                $path = Storage::disk('public')->path($fixture['path']);

                return [
                    'path' => $fixture['path'],
                    'original_name' => $fixture['original_name'],
                    'size_bytes' => max(0, (int) filesize($path)),
                ];
            },
            self::IMAGE_PATHS,
        );
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Category>  $categories
     * @param  list<array{path: string, original_name: string, size_bytes: int}>  $fixtures
     */
    private function seedAds(Collection $users, Collection $categories, array $fixtures): void
    {
        $total = max(1, (int) config('seeding.ads_total', 100_000));
        $batchSize = max(1, (int) config('seeding.ads_batch_size', 1_000));
        $now = now();
        $adBatch = [];
        $metaBatch = [];

        for ($sequence = 1; $sequence <= $total; $sequence++) {
            $category = $this->categoryFor($categories, $sequence);
            $user = $this->userFor($users, $sequence);
            $row = $this->adRow($sequence, $user->id, $category, $now);

            $adBatch[] = $row;
            $metaBatch[] = ['slug' => $row['slug'], 'image_count' => AdSeederProfile::imageCount($sequence)];

            if (count($adBatch) < $batchSize) {
                continue;
            }

            $this->persistBatch($adBatch, $metaBatch, $fixtures, $now);
            $adBatch = [];
            $metaBatch = [];
        }

        if ($adBatch !== []) {
            $this->persistBatch($adBatch, $metaBatch, $fixtures, $now);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $adBatch
     * @param  array<int, array{slug: string, image_count: int}>  $metaBatch
     * @param  list<array{path: string, original_name: string, size_bytes: int}>  $fixtures
     */
    private function persistBatch(array $adBatch, array $metaBatch, array $fixtures, CarbonInterface $now): void
    {
        try {
            DB::transaction(function () use ($adBatch, $metaBatch, $fixtures, $now): void {
                Ad::query()->insert($adBatch);

                $idsBySlug = Ad::query()
                    ->whereIn('slug', array_column($metaBatch, 'slug'))
                    ->pluck('id', 'slug')
                    ->all();

                $imageRows = $this->imageRows($metaBatch, $idsBySlug, $fixtures, $now);

                if ($imageRows !== []) {
                    AdImage::query()->insert($imageRows);
                }
            }, 3);
        } catch (QueryException $exception) {
            throw new \RuntimeException(
                'AdSeeder stopped while persisting a batch: '.$exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * @param  array<int, array{slug: string, image_count: int}>  $metaBatch
     * @param  array<string, int>  $idsBySlug
     * @param  list<array{path: string, original_name: string, size_bytes: int}>  $fixtures
     * @return list<array<string, mixed>>
     */
    private function imageRows(array $metaBatch, array $idsBySlug, array $fixtures, CarbonInterface $now): array
    {
        $rows = [];

        foreach ($metaBatch as $offset => $meta) {
            $adId = $idsBySlug[$meta['slug']] ?? null;

            if ($adId === null) {
                continue;
            }

            for ($position = 0; $position < $meta['image_count']; $position++) {
                $fixture = $fixtures[($offset + $position) % count($fixtures)];
                $rows[] = [
                    'ad_id' => $adId,
                    'disk' => 'public',
                    'path' => $fixture['path'],
                    'original_name' => $fixture['original_name'],
                    'size_bytes' => $fixture['size_bytes'],
                    'position' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function categoryFor(Collection $categories, int $sequence): Category
    {
        return $categories[($sequence - 1) % $categories->count()];
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function userFor(Collection $users, int $sequence): User
    {
        return $users[($sequence - 1) % $users->count()];
    }

    /**
     * @return array{
     *     user_id: int,
     *     category_id: int,
     *     title: string,
     *     slug: string,
     *     description: string,
     *     price: string|null,
     *     location: string,
     *     latitude: float,
     *     longitude: float,
     *     contact_email: string|null,
     *     contact_phone: string|null,
     *     status: string,
     *     rejection_reason: null,
     *     published_at: CarbonInterface,
     *     expires_at: CarbonInterface,
     *     terms_accepted_at: CarbonInterface,
     *     views_count: int,
     *     is_negotiable: bool,
     *     condition: string|null,
     *     delivery_methods: string,
     *     delivery_prices: string,
     *     phone_reveals_count: int,
     *     created_at: CarbonInterface,
     *     updated_at: CarbonInterface
     * }
     */
    private function adRow(int $sequence, int $userId, Category $category, CarbonInterface $now): array
    {
        $rootSlug = $this->rootSlug($category);
        $title = AdSeederProfile::title($rootSlug, $sequence);

        return [
            'user_id' => $userId,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => AdSeederProfile::slug($title, $sequence),
            'description' => AdSeederProfile::description($rootSlug, $category->name, $sequence),
            'price' => AdSeederProfile::price($rootSlug, $sequence),
            'location' => AdSeederProfile::location($sequence),
            'latitude' => AdSeederProfile::coordinates($sequence)[0],
            'longitude' => AdSeederProfile::coordinates($sequence)[1],
            'contact_email' => null,
            'contact_phone' => $sequence % 3 === 0 ? sprintf('+48 500 %03d %03d', $sequence % 1_000, ($sequence * 7) % 1_000) : null,
            'status' => 'active',
            'rejection_reason' => null,
            'published_at' => $now->copy()->subDays($sequence % 45)->subHours($sequence % 24),
            'expires_at' => $now->copy()->addDays(30 - ($sequence % 10)),
            'terms_accepted_at' => $now,
            'views_count' => $sequence % 7_500,
            'is_negotiable' => AdSeederProfile::isNegotiable($rootSlug, $sequence),
            'condition' => AdSeederProfile::condition($rootSlug, $sequence),
            'delivery_methods' => json_encode(AdSeederProfile::deliveryMethods($rootSlug, $sequence), JSON_THROW_ON_ERROR),
            'delivery_prices' => json_encode(AdSeederProfile::deliveryPrices($rootSlug, $sequence), JSON_THROW_ON_ERROR),
            'phone_reveals_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function rootSlug(Category $category): string
    {
        $ancestor = $category->ancestors->last();

        return $ancestor instanceof Category ? $ancestor->slug : $category->slug;
    }
}
