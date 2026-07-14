<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Lokalny katalog zdjęć dopasowanych do szablonów ogłoszeń demo.
 * Pliki trafiają do database/seeders/assets/demo/ — seeder kopiuje je do storage.
 */
final class DemoMarketplaceImageStore
{
    private const string USER_AGENT = 'ZuntoDemoSeeder/1.0 (demo-marketplace; contact@zunto.local)';

    /** @var array<string, string> */
    private const array FIXED_IMAGE_SEARCH_QUERIES = [
        'gondola-cybex' => 'cybex baby stroller pram',
    ];

    public static function assetsDirectory(): string
    {
        return database_path('seeders/assets/demo');
    }

    public static function manifestPath(): string
    {
        return self::assetsDirectory().'/manifest.json';
    }

    public static function assetAbsolutePath(string $imageName): string
    {
        return self::assetsDirectory().'/'.self::normalizeImageName($imageName);
    }

    public static function assetExists(string $imageName): bool
    {
        return is_file(self::assetAbsolutePath($imageName));
    }

    public static function normalizeImageName(string $imageName): string
    {
        $basename = basename($imageName);
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return Str::slug(pathinfo($basename, PATHINFO_FILENAME)).'.jpg';
        }

        return $basename;
    }

    /**
     * @return array<string, array{title: string, category_slug: string, image_name: string}>
     */
    public static function requiredImages(): array
    {
        $required = [];

        foreach (DemoMarketplaceCatalog::canonicalCategorySlugs() as $categorySlug) {
            for ($index = 1; $index <= 20; $index++) {
                $listing = DemoMarketplaceCatalog::listing($categorySlug, $index);
                $imageName = self::normalizeImageName($listing['image_name']);

                if (! isset($required[$imageName])) {
                    $required[$imageName] = [
                        'title' => $listing['title'],
                        'category_slug' => $categorySlug,
                        'image_name' => $imageName,
                    ];
                }
            }
        }

        ksort($required);

        return $required;
    }

    /**
     * @return list<string>
     */
    public static function searchFallbacks(string $title, string $categorySlug): array
    {
        $base = self::asciiTitle($title);
        $short = trim((string) preg_replace('/\s*[-—].*$/u', '', $base));
        $short = trim((string) preg_replace('/\b\d{2,4}\b/', '', $short));
        $short = trim((string) preg_replace('/\s{2,}/', ' ', $short));

        $words = preg_split('/\s+/', $short) ?: [];
        $words = array_values(array_filter($words, static fn (string $word): bool => mb_strlen($word) > 1));
        $compact = implode(' ', array_slice($words, 0, 4));
        $minimal = implode(' ', array_slice($words, 0, 2));

        $hint = self::englishHint($categorySlug);
        $translated = self::translateKeywords($short, $categorySlug);

        $queries = array_values(array_unique(array_filter([
            trim($compact.' '.$hint),
            trim($minimal.' '.$hint),
            $translated,
            $compact,
            $minimal,
            $hint,
        ])));

        return $queries;
    }

    public static function searchQuery(string $title, string $categorySlug): string
    {
        return self::searchFallbacks($title, $categorySlug)[0] ?? $title;
    }

    public static function searchQueryForListing(string $imageName, string $title, string $categorySlug): string
    {
        $stemQuery = self::searchQueryFromImageStem($imageName, $categorySlug);

        if ($stemQuery !== null) {
            return $stemQuery;
        }

        if (self::isGenericListingTitle($title)) {
            return self::englishHint($categorySlug);
        }

        return self::searchQuery($title, $categorySlug);
    }

    /**
     * @return list<string>
     */
    public static function searchFallbacksForListing(string $imageName, string $title, string $categorySlug): array
    {
        $stemQuery = self::searchQueryFromImageStem($imageName, $categorySlug);

        if ($stemQuery !== null) {
            return array_values(array_unique(array_filter([
                $stemQuery,
                self::searchQuery($title, $categorySlug),
                self::englishHint($categorySlug),
            ])));
        }

        return self::searchFallbacks($title, $categorySlug);
    }

    /**
     * @return array{url: string, title: string}|null
     */
    public static function findWikimediaImageForListingMeta(string $imageName, string $title, string $categorySlug): ?array
    {
        foreach (self::searchFallbacksForListing($imageName, $title, $categorySlug) as $query) {
            $match = self::findWikimediaImage($query);

            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    private static function searchQueryFromImageStem(string $imageName, string $categorySlug): ?string
    {
        $stem = pathinfo(self::normalizeImageName($imageName), PATHINFO_FILENAME);

        if (isset(self::FIXED_IMAGE_SEARCH_QUERIES[$stem])) {
            return self::FIXED_IMAGE_SEARCH_QUERIES[$stem];
        }

        if ($stem === '' || preg_match('/^oferta(-\d+)?$/', $stem) === 1) {
            return null;
        }

        $tokens = preg_split('/-+/', $stem) ?: [];
        $tokens = array_values(array_filter($tokens, static fn (string $token): bool => mb_strlen($token) > 1));

        if ($tokens === []) {
            return null;
        }

        return trim(implode(' ', $tokens).' '.self::englishHint($categorySlug));
    }

    private static function isGenericListingTitle(string $title): bool
    {
        $lower = mb_strtolower($title);

        if (preg_match('/^zestaw\b/u', $lower) === 1) {
            return true;
        }

        foreach ([
            'oferta prywatna',
            'zestaw w bardzo dobrym stanie',
            'okazja — szybka transakcja',
            'sprzedaż po sezonie',
            'jak na zdjęciach',
            'stan jak na zdjęciach',
            '— zestaw ',
            'zestaw 5 szt',
            'zestaw vintage',
            'zestaw 12 szt',
            'zestaw 20 szt',
        ] as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private static function englishHint(string $categorySlug): string
    {
        return match ($categorySlug) {
            'samochody' => 'car',
            'dostawcze-i-ciezarowe' => 'van truck',
            'motocykle-i-skutery' => 'motorcycle',
            'opony-i-felgi' => 'car wheel tire',
            'oleje-i-plyny' => 'motor oil',
            'czesci-karoserii' => 'car part',
            'przyczepy-i-naczepy' => 'trailer',
            'serwis-i-naprawa' => 'car repair shop',
            'laptopy' => 'laptop',
            'telefony', 'telefony-komorkowe' => 'smartphone',
            'komponenty' => 'computer component',
            'peryferia' => 'computer mouse keyboard monitor',
            'rtv-i-audio' => 'headphones speaker',
            'aparaty-fotograficzne' => 'digital camera',
            'konsole-i-gry' => 'game console',
            'akcesoria-elektroniczne' => 'phone accessory',
            'meble' => 'furniture',
            'agd' => 'home appliance',
            'rowery' => 'bicycle',
            'zabawki' => 'toy',
            'mieszkania', 'pokoje-i-stancje' => 'apartment interior',
            'domy' => 'detached house',
            'dzialki-i-grunty' => 'land plot',
            'lokale-i-biura', 'garaze-i-parkingi' => 'office building',
            'oferty-pracy', 'szukam-pracy', 'freelance', 'praktyki-i-staze' => 'office worker',
            'budowlane' => 'construction site',
            'naprawy', 'serwis-i-naprawa' => 'repair workshop',
            'transportowe' => 'moving truck',
            'sprzatanie' => 'cleaning service',
            'nauka-i-korepetycje' => 'tutoring classroom',
            'odziez-damska', 'odziez-meska', 'ubranka-dzieciece' => 'clothing',
            'obuwie' => 'shoes',
            'bizuteria', 'akcesoria-modowe' => 'jewelry',
            'wozki-dzieciece' => 'baby stroller',
            'foteliki-samochodowe' => 'child car seat',
            'silownia-i-fitness' => 'gym equipment',
            'turystyka' => 'camping tent',
            'muzyka-i-instrumenty' => 'guitar piano',
            'kolekcje' => 'collectible coins stamps',
            'ogrod' => 'garden tools',
            'narzedzia' => 'power tools',
            'dekoracje' => 'home decoration',
            'lampy-sufitowe', 'lampki-biurkowe', 'zrodla-swiatla' => 'lamp',
            'pozostale-uslugi' => 'service business',
            default => 'product',
        };
    }

    private static function asciiTitle(string $title): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);

        if ($ascii === false) {
            $ascii = $title;
        }

        return trim((string) preg_replace('/[^A-Za-z0-9\s\/\"\.]/', ' ', $ascii));
    }

    private static function translateKeywords(string $title, string $categorySlug): string
    {
        $lower = mb_strtolower($title);

        $replacements = [
            'zderzak przedni' => 'front bumper',
            'zderzak' => 'bumper',
            'drzwi lewe przednie' => 'car door',
            'drzwi' => 'door',
            'maska silnika' => 'car hood',
            'maska' => 'hood',
            'blotnik' => 'car fender',
            'klapa bagaznika' => 'car trunk lid',
            'opony zimowe' => 'winter tires',
            'opony letnie' => 'summer tires',
            'felgi aluminiowe' => 'alloy wheels',
            'felgi' => 'wheels',
            'pralka' => 'washing machine',
            'lodowka' => 'refrigerator',
            'zmywarka' => 'dishwasher',
            'odkurzacz' => 'vacuum cleaner',
            'ekspres' => 'espresso machine',
            'sofa narozna' => 'corner sofa',
            'stol debowy' => 'oak dining table',
            'lozko' => 'bed frame',
            'regal' => 'bookshelf',
            'wozek dzieciecy' => 'baby stroller',
            'fotelik samochodowy' => 'child car seat',
            'kurtka' => 'jacket',
            'sukienka' => 'dress',
            'jeansy' => 'jeans',
            'przeprowadzki' => 'moving service',
            'wulkanizacja' => 'tire service',
            'geometria' => 'wheel alignment',
            'klimatyzacji samochodowej' => 'car air conditioning',
            'naprawa laptopow' => 'laptop repair',
            'naprawa telefonow' => 'phone repair',
            'naprawa rowerow' => 'bicycle repair',
            'mieszkanie' => 'apartment',
            'kawalerka' => 'studio apartment',
            'dom jednorodzinny' => 'detached house',
            'dzialka' => 'land plot',
        ];

        foreach ($replacements as $polish => $english) {
            if (str_contains($lower, $polish)) {
                return trim($english.' '.self::englishHint($categorySlug));
            }
        }

        return '';
    }

    /**
     * @return array{url: string, title: string}|null
     */
    public static function findWikimediaImageForListing(string $title, string $categorySlug): ?array
    {
        foreach (self::searchFallbacks($title, $categorySlug) as $query) {
            $match = self::findWikimediaImage($query);

            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    /**
     * @return array{url: string, title: string}|null
     */
    public static function findWikimediaImage(string $query): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->get('https://commons.wikimedia.org/w/api.php', [
                    'action' => 'query',
                    'format' => 'json',
                    'generator' => 'search',
                    'gsrsearch' => $query,
                    'gsrnamespace' => 6,
                    'gsrlimit' => 8,
                    'prop' => 'imageinfo',
                    'iiprop' => 'url|mime|size',
                    'iiurlwidth' => 900,
                ]);

            if (! $response->successful()) {
                return null;
            }

            /** @var array<string, mixed> $payload */
            $payload = $response->json();
            $pages = $payload['query']['pages'] ?? [];

            if (! is_array($pages)) {
                return null;
            }

            foreach ($pages as $page) {
                if (! is_array($page)) {
                    continue;
                }

                $info = $page['imageinfo'][0] ?? null;

                if (! is_array($info)) {
                    continue;
                }

                $mime = (string) ($info['mime'] ?? '');
                $url = (string) ($info['thumburl'] ?? $info['url'] ?? '');

                if ($url === '') {
                    continue;
                }

                if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    continue;
                }

                $width = (int) ($info['thumbwidth'] ?? $info['width'] ?? 0);

                if ($width > 0 && $width < 320) {
                    continue;
                }

                return [
                    'url' => $url,
                    'title' => (string) ($page['title'] ?? ''),
                ];
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    public static function downloadToAsset(string $url, string $imageName): bool
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders(['User-Agent' => self::USER_AGENT])
                ->get($url);

            if (! $response->successful()) {
                return false;
            }

            $body = $response->body();

            if ($body === '') {
                return false;
            }

            if (! is_dir(self::assetsDirectory())) {
                mkdir(self::assetsDirectory(), 0775, true);
            }

            $written = file_put_contents(self::assetAbsolutePath($imageName), $body);

            return $written !== false && $written > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  callable(string, string): void|null  $onProgress  ($status, $imageName) — status: ok|skip|fail
     * @return array{downloaded: int, skipped: int, failed: int, failures: list<string>}
     */
    public static function fetchRequiredImages(bool $force = false, ?callable $onProgress = null): array
    {
        $required = self::requiredImages();
        $manifest = self::loadManifest();

        $downloaded = 0;
        $skipped = 0;
        $failed = 0;
        $failures = [];

        foreach ($required as $imageName => $meta) {
            if (! $force && self::assetExists($imageName)) {
                $skipped++;
                if ($onProgress !== null) {
                    $onProgress('skip', $imageName);
                }

                continue;
            }

            $match = self::findWikimediaImageForListingMeta(
                $meta['image_name'],
                $meta['title'],
                $meta['category_slug'],
            );

            if ($match === null) {
                $manifestUrl = (string) ($manifest[$imageName]['source_url'] ?? '');

                if ($manifestUrl !== '' && self::downloadToAsset($manifestUrl, $imageName)) {
                    $match = [
                        'url' => $manifestUrl,
                        'title' => (string) ($manifest[$imageName]['source_title'] ?? ''),
                    ];
                }
            }

            if ($match === null) {
                $failed++;
                $failures[] = $imageName;
                if ($onProgress !== null) {
                    $onProgress('fail', $imageName);
                }

                continue;
            }

            if (! self::downloadToAsset($match['url'], $imageName)) {
                $failed++;
                $failures[] = $imageName;

                if ($onProgress !== null) {
                    $onProgress('fail', $imageName);
                }

                continue;
            }

            $manifest[$imageName] = [
                'search' => self::searchQueryForListing(
                    $meta['image_name'],
                    $meta['title'],
                    $meta['category_slug'],
                ),
                'category_slug' => $meta['category_slug'],
                'listing_title' => $meta['title'],
                'source_url' => $match['url'],
                'source_title' => $match['title'],
                'downloaded_at' => now()->toIso8601String(),
            ];

            $downloaded++;

            if ($onProgress !== null) {
                $onProgress('ok', $imageName);
            }

            usleep(250_000);
        }

        self::saveManifest($manifest);

        return [
            'downloaded' => $downloaded,
            'skipped' => $skipped,
            'failed' => $failed,
            'failures' => $failures,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function loadManifest(): array
    {
        $path = self::manifestPath();

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $manifest
     */
    public static function saveManifest(array $manifest): void
    {
        if (! is_dir(self::assetsDirectory())) {
            mkdir(self::assetsDirectory(), 0775, true);
        }

        file_put_contents(
            self::manifestPath(),
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
    }
}
