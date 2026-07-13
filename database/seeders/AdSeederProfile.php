<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdCondition;
use Illuminate\Support\Str;

final class AdSeederProfile
{
    /**
     * @var list<string>
     */
    private const array CITIES = [
        'Warszawa', 'Kraków', 'Wrocław', 'Poznań', 'Gdańsk', 'Łódź', 'Katowice',
        'Lublin', 'Szczecin', 'Bydgoszcz', 'Białystok', 'Rzeszów', 'Toruń', 'Kielce',
    ];

    /**
     * @var list<string>
     */
    private const array DISTRICTS = [
        'Mokotów', 'Wola', 'Praga-Południe', 'Śródmieście', 'Ursynów', 'Ochota',
        'Bielany', 'Krzyki', 'Stare Miasto', 'Jeżyce', 'Wrzeszcz', 'Bronowice',
    ];

    public static function sellerEmail(int $sequence): string
    {
        return sprintf('seed-seller-%03d@ogloszenia.local', $sequence);
    }

    public static function sellerName(int $sequence): string
    {
        return sprintf('Sprzedawca %03d', $sequence);
    }

    public static function slug(string $title, int $sequence): string
    {
        return sprintf('seed-ads-%06d-%s', $sequence, Str::slug($title));
    }

    public static function title(string $rootSlug, int $sequence): string
    {
        $pool = self::titlePool($rootSlug);

        return $pool[($sequence - 1) % count($pool)];
    }

    public static function description(string $rootSlug, string $categoryName, int $sequence): string
    {
        $intro = match ($rootSlug) {
            'motoryzacja' => 'Samochód z polskiego rynku, gotowy do jazdy.',
            'nieruchomosci' => 'Oferta przygotowana do szybkiego kontaktu i oględzin.',
            'elektronika' => 'Sprzęt sprawny, używany prywatnie, bez blokad i ukrytych wad.',
            'dom-i-ogrod' => 'Przedmiot zadbany, dobrze trzyma się w codziennym użytkowaniu.',
            'moda' => 'Stan bardzo dobry, bez zniszczeń i przebarwień.',
            'dla-dzieci' => 'Komplet używany w domu, czysty i gotowy do odbioru.',
            'sport-i-hobby' => 'Sprzęt po serwisie, nadaje się do dalszego używania.',
            'praca' => 'Szczegóły oferty w wiadomości, szybki start możliwy.',
            'uslugi' => 'Termin do ustalenia, wycena po kontakcie i krótkim opisie potrzeb.',
            default => 'Opis ogłoszenia demonstracyjnego.',
        };

        $city = self::location($sequence);

        return $intro." Kategoria: {$categoryName}. Lokalizacja: {$city}. Ogłoszenie ".$sequence.'.';
    }

    public static function price(string $rootSlug, int $sequence): ?string
    {
        return match ($rootSlug) {
            'motoryzacja' => self::money(8_500 + ($sequence % 120_000)),
            'nieruchomosci' => self::money(180_000 + ($sequence % 900_000)),
            'elektronika' => self::money(120 + ($sequence % 12_000)),
            'dom-i-ogrod' => self::money(40 + ($sequence % 8_000)),
            'moda' => self::money(35 + ($sequence % 700)),
            'dla-dzieci' => self::money(25 + ($sequence % 5_000)),
            'sport-i-hobby' => self::money(60 + ($sequence % 18_000)),
            'praca' => self::money(4_200 + ($sequence % 7_000)),
            'uslugi' => self::money(90 + ($sequence % 15_000)),
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public static function deliveryMethods(string $rootSlug, int $sequence): array
    {
        if (in_array($rootSlug, ['nieruchomosci', 'praca', 'uslugi'], true)) {
            return [];
        }

        $methods = ['personal', 'courier', 'parcel_locker', 'post', 'local'];
        $count = 2 + ($sequence % 2);

        return array_slice($methods, 0, $count);
    }

    /**
     * @return array<string, string>
     */
    public static function deliveryPrices(string $rootSlug, int $sequence): array
    {
        $methods = self::deliveryMethods($rootSlug, $sequence);

        if ($methods === []) {
            return [];
        }

        $prices = [];

        foreach ($methods as $offset => $method) {
            $prices[$method] = $method === 'personal'
                ? '0.00'
                : self::money(9 + ($sequence % 25) + ($offset * 4));
        }

        return $prices;
    }

    public static function imageCount(int $sequence): int
    {
        $min = max(1, (int) config('seeding.images_per_ad_min', 2));
        $max = max($min, (int) config('seeding.images_per_ad_max', 3));

        return $min + ($sequence % ($max - $min + 1));
    }

    public static function condition(string $rootSlug, int $sequence): ?string
    {
        if (in_array($rootSlug, ['nieruchomosci', 'praca', 'uslugi'], true)) {
            return null;
        }

        $cases = AdCondition::cases();

        return $cases[$sequence % count($cases)]->value;
    }

    public static function isNegotiable(string $rootSlug, int $sequence): bool
    {
        if (in_array($rootSlug, ['nieruchomosci', 'praca'], true)) {
            return $sequence % 3 !== 0;
        }

        return $sequence % 4 === 0;
    }

    public static function location(int $sequence): string
    {
        return self::CITIES[$sequence % count(self::CITIES)];
    }

    public static function district(int $sequence): ?string
    {
        return $sequence % 2 === 0 ? self::DISTRICTS[$sequence % count(self::DISTRICTS)] : null;
    }

    /**
     * @return list<string>
     */
    private static function titlePool(string $rootSlug): array
    {
        return match ($rootSlug) {
            'motoryzacja' => [
                'Audi A4 Avant 2.0 TDI',
                'BMW 320d Touring',
                'Honda Civic 1.5 Turbo',
                'Mercedes C200 kombi',
                'Zestaw opon zimowych 205/55 R16',
                'Przyczepa lekka z plandeką',
            ],
            'nieruchomosci' => [
                'Mieszkanie 3 pokoje po remoncie',
                'Dom z ogrodem i garażem',
                'Działka budowlana 1200 m2',
                'Lokal użytkowy na parterze',
                'Pokój do wynajęcia blisko centrum',
            ],
            'elektronika' => [
                'Laptop Lenovo ThinkPad T14',
                'iPhone 13 128 GB',
                'PlayStation 5 z dwoma padami',
                'Monitor 27 cali IPS 144 Hz',
                'Konsola Nintendo Switch OLED',
            ],
            'dom-i-ogrod' => [
                'Sofa narożna do salonu',
                'Stół dębowy rozkładany',
                'Lampa wisząca loft',
                'Kosiarka elektryczna',
                'Komplet mebli ogrodowych',
            ],
            'moda' => [
                'Kurtka zimowa damska',
                'Buty skórzane męskie',
                'Torebka skórzana premium',
                'Zegarek klasyczny',
                'Płaszcz wełniany',
            ],
            'dla-dzieci' => [
                'Wózek dziecięcy 3w1',
                'Fotelik samochodowy 0-13 kg',
                'Zabawki edukacyjne dla malucha',
                'Ubranka dziecięce zestaw',
                'Łóżeczko turystyczne',
            ],
            'sport-i-hobby' => [
                'Rower trekkingowy 28 cali',
                'Hantle regulowane',
                'Gitara akustyczna',
                'Namiot rodzinny 4 osobowy',
                'Zestaw do biegania',
            ],
            'praca' => [
                'Oferta pracy - magazynier',
                'Oferta pracy - specjalista SEO',
                'Szukam pracy - grafik',
                'Szukam pracy - kierowca kat. B',
                'Praktyki IT dla studenta',
            ],
            'uslugi' => [
                'Remont mieszkania od A do Z',
                'Transport krajowy i przeprowadzki',
                'Sprzątanie biur i mieszkań',
                'Naprawa sprzętu AGD',
                'Korepetycje z matematyki',
            ],
            default => ['Ogłoszenie demonstracyjne'],
        };
    }

    private static function money(int $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
