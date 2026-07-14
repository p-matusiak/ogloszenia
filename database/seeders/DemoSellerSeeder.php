<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdCondition;
use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Jeden sprzedawca z kilkoma profesjonalnymi ogłoszeniami — do testów nawigacji
 * między ofertami tego samego autora i strony /sprzedawca/{id}.
 */
final class DemoSellerSeeder extends Seeder
{
    private const string SELLER_EMAIL = 'demo-seller@zunto.local';

    private const string SLUG_PREFIX = 'demo-techpoint';

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
        $this->clearDemoAds();

        $seller = User::query()->updateOrCreate(
            ['email' => self::SELLER_EMAIL],
            [
                'name' => 'TechPoint — Anna Kowalska',
                'slug' => 'techpoint-anna-kowalska',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'bio' => 'Sprzedaję sprawdzoną elektronikę używaną i odnowioną. Każdy sprzęt testuję przed wystawieniem, dołączam zdjęcia rzeczywistego stanu i szybko odpowiadam na wiadomości.',
                'phone' => '+48 501 234 567',
                'is_admin' => false,
            ],
        );

        $category = Category::query()
            ->where('slug', 'laptopy')
            ->first()
            ?? Category::query()->whereDoesntHave('children')->orderBy('id')->first();

        if ($category === null) {
            return;
        }

        $now = now();
        $fixtures = $this->imageFixtures();

        foreach ($this->adDefinitions() as $index => $definition) {
            $sequence = $index + 1;
            $slug = sprintf('%s-%s', self::SLUG_PREFIX, $definition['slug_suffix']);

            $ad = Ad::query()->create([
                'user_id' => $seller->id,
                'category_id' => $category->id,
                'title' => $definition['title'],
                'slug' => $slug,
                'description' => $definition['description'],
                'price' => $definition['price'],
                'is_negotiable' => $definition['negotiable'],
                'condition' => $definition['condition'],
                'delivery_methods' => $definition['delivery_methods'],
                'delivery_prices' => $definition['delivery_prices'],
                'location' => 'Warszawa, Mokotów',
                'latitude' => 52.2040093,
                'longitude' => 21.0287184,
                'contact_phone' => null,
                'status' => AdStatus::Active,
                'published_at' => $now->copy()->subDays($sequence),
                'expires_at' => $now->copy()->addDays(30),
                'terms_accepted_at' => $now,
                'views_count' => 120 + ($sequence * 37),
            ]);

            $this->attachImages($ad->id, $sequence, $fixtures, $now);
        }
    }

    private function clearDemoAds(): void
    {
        Ad::query()
            ->where('slug', 'like', self::SLUG_PREFIX.'-%')
            ->delete();
    }

    /**
     * @return list<array{
     *     slug_suffix: string,
     *     title: string,
     *     description: string,
     *     price: string,
     *     negotiable: bool,
     *     condition: string,
     *     delivery_methods: list<string>,
     *     delivery_prices: array<string, string>
     * }>
     */
    private function adDefinitions(): array
    {
        return [
            [
                'slug_suffix' => 'macbook-pro-14-m1',
                'title' => 'MacBook Pro 14" M1 Pro 16 GB / 512 GB',
                'description' => "Laptop w bardzo dobrym stanie, używany do pracy biurowej.\n\nSpecyfikacja:\n• Apple M1 Pro\n• 16 GB RAM\n• 512 GB SSD\n• Bateria ok. 88% capacity\n\nW zestawie oryginalna ładowarka USB-C 67 W. Bez rys na ekranie, obudowa zadbana. Odbiór osobisty Mokotów lub wysyłka InPost.",
                'price' => '4899.00',
                'negotiable' => true,
                'condition' => AdCondition::Used->value,
                'delivery_methods' => ['personal', 'parcel_locker', 'courier'],
                'delivery_prices' => ['personal' => '0.00', 'parcel_locker' => '19.99', 'courier' => '24.99'],
            ],
            [
                'slug_suffix' => 'iphone-13-128',
                'title' => 'iPhone 13 128 GB — Midnight, bateria 91%',
                'description' => "Telefon z polskiej dystrybucji, bez blokad operatorki.\n\nStan: obudowa bez pęknięć, ekran bez rys, Face ID i aparaty w pełni sprawne. Bateria 91% według ustawień iOS. W zestawie pudełko i kabel Lightning.\n\nMożliwa wysyłka za pobraniem lub odbiór w Warszawie.",
                'price' => '1899.00',
                'negotiable' => false,
                'condition' => AdCondition::Used->value,
                'delivery_methods' => ['personal', 'courier', 'post'],
                'delivery_prices' => ['personal' => '0.00', 'courier' => '18.00', 'post' => '16.00'],
            ],
            [
                'slug_suffix' => 'monitor-dell-27',
                'title' => 'Monitor Dell UltraSharp 27" QHD IPS',
                'description' => "Panel IPS 2560×1440, 60 Hz, porty HDMI i DisplayPort.\n\nIdealny do pracy zdalnej i grafiki. Brak wypaleń, obudowa bez uszkodzeń. Sprzedaję, bo przeszedłem na szerszy ultrawide.\n\nOdbiór Mokotów; mogę pomóc w załadowaniu do auta.",
                'price' => '899.00',
                'negotiable' => true,
                'condition' => AdCondition::Used->value,
                'delivery_methods' => ['personal', 'local'],
                'delivery_prices' => ['personal' => '0.00', 'local' => '49.00'],
            ],
            [
                'slug_suffix' => 'ipad-air-5',
                'title' => 'iPad Air 5 gen. Wi-Fi 64 GB + Apple Pencil 2',
                'description' => "Tablet kupiony w 2023, używany sporadycznie do notatek i szkiców.\n\nW zestawie:\n• iPad Air 5 (różowy)\n• Apple Pencil 2 generacji\n• etui z klapką\n\nBez rys na ekranie, iCloud wylogowany. Idealny do studiów i pracy kreatywnej.",
                'price' => '2299.00',
                'negotiable' => true,
                'condition' => AdCondition::Used->value,
                'delivery_methods' => ['personal', 'parcel_locker'],
                'delivery_prices' => ['personal' => '0.00', 'parcel_locker' => '17.99'],
            ],
            [
                'slug_suffix' => 'sony-wh1000xm5',
                'title' => 'Sony WH-1000XM5 — słuchawki ANC jak nowe',
                'description' => "Kupione 8 miesięcy temu, intensywnie używane w podróżach.\n\nPełna sprawność ANC i multipoint. W zestawie futerał, kabel audio i adapter lotniczy. Bateria trzyma jak w dniu zakupu.\n\nWysyłka InPost lub odbiór osobisty.",
                'price' => '999.00',
                'negotiable' => false,
                'condition' => AdCondition::Used->value,
                'delivery_methods' => ['personal', 'parcel_locker', 'courier'],
                'delivery_prices' => ['personal' => '0.00', 'parcel_locker' => '14.99', 'courier' => '19.99'],
            ],
            [
                'slug_suffix' => 'thinkpad-t14-gen2',
                'title' => 'Lenovo ThinkPad T14 Gen 2 — i5, 16 GB, FHD',
                'description' => "Laptop firmowy po lease, profesjonalnie przygotowany do sprzedaży.\n\n• Intel Core i5-1145G7\n• 16 GB DDR4\n• 512 GB NVMe\n• Windows 11 Pro (licencja OEM)\n• Klawiatura PL, podświetlenie\n\nSprawny, bez problemów z zawiasami. Faktura VAT-marża na życzenie.",
                'price' => '2199.00',
                'negotiable' => true,
                'condition' => AdCondition::Used->value,
                'delivery_methods' => ['personal', 'courier'],
                'delivery_prices' => ['personal' => '0.00', 'courier' => '22.00'],
            ],
        ];
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
     * @param  list<array{path: string, original_name: string, size_bytes: int}>  $fixtures
     */
    private function attachImages(int $adId, int $sequence, array $fixtures, Carbon $now): void
    {
        $fixture = $fixtures[($sequence - 1) % count($fixtures)];

        AdImage::query()->create([
            'ad_id' => $adId,
            'disk' => 'public',
            'path' => $fixture['path'],
            'original_name' => $fixture['original_name'],
            'size_bytes' => $fixture['size_bytes'],
            'position' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
