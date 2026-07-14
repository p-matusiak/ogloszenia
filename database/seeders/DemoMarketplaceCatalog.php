<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdCondition;
use Illuminate\Support\Str;

/**
 * Katalog realistycznych ogłoszeń demo — tytuły, opisy i zdjęcia dopasowane do kategorii.
 */
final class DemoMarketplaceCatalog
{
    public const string SLUG_PREFIX = 'rynek';

    public const string SELLER_EMAIL_PREFIX = 'rynek-';

    /**
     * @return list<string>
     */
    public static function canonicalCategorySlugs(): array
    {
        return [
            'samochody', 'motocykle-i-skutery', 'opony-i-felgi', 'oleje-i-plyny', 'czesci-karoserii',
            'dostawcze-i-ciezarowe', 'przyczepy-i-naczepy', 'serwis-i-naprawa',
            'mieszkania', 'domy', 'dzialki-i-grunty', 'lokale-i-biura', 'garaze-i-parkingi', 'pokoje-i-stancje',
            'telefony', 'laptopy', 'komponenty', 'peryferia', 'rtv-i-audio', 'aparaty-fotograficzne',
            'konsole-i-gry', 'akcesoria-elektroniczne',
            'meble', 'ogrod', 'lampy-sufitowe', 'lampki-biurkowe', 'zrodla-swiatla', 'agd', 'narzedzia', 'dekoracje',
            'odziez-damska', 'odziez-meska', 'obuwie', 'bizuteria', 'akcesoria-modowe',
            'wozki-dzieciece', 'foteliki-samochodowe', 'ubranka-dzieciece', 'zabawki', 'akcesoria-dla-dzieci',
            'rowery', 'silownia-i-fitness', 'turystyka', 'muzyka-i-instrumenty', 'kolekcje',
            'oferty-pracy', 'szukam-pracy', 'freelance', 'praktyki-i-staze',
            'budowlane', 'transportowe', 'sprzatanie', 'nauka-i-korepetycje', 'naprawy', 'pozostale-uslugi',
        ];
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     price: string|null,
     *     is_negotiable: bool,
     *     condition: string|null,
     *     delivery_methods: list<string>,
     *     delivery_prices: array<string, string>,
     *     image_name: string
     * }
     */
    public static function listing(string $categorySlug, int $index): array
    {
        $baseItems = self::baseItemsFor($categorySlug);
        $base = $baseItems[($index - 1) % count($baseItems)];

        return self::varyListing($base, $categorySlug, $index);
    }

    /**
     * @return array{name: string, email: string, bio: string|null}
     */
    public static function seller(int $sequence): array
    {
        $name = self::SELLER_NAMES[($sequence - 1) % count(self::SELLER_NAMES)];
        $emailSlug = Str::slug($name);
        $emailSlug = $emailSlug !== '' ? $emailSlug : 'sprzedawca-'.$sequence;

        return [
            'name' => $name,
            'email' => self::SELLER_EMAIL_PREFIX.$emailSlug.'@zunto.local',
            'bio' => self::sellerBio($name, $sequence),
        ];
    }

    public static function adSlug(string $categorySlug, int $index, string $title): string
    {
        return sprintf(
            '%s-%s-%03d-%s',
            self::SLUG_PREFIX,
            $categorySlug,
            $index,
            Str::slug(Str::limit($title, 60, '')),
        );
    }

    /**
     * @return array{location: string, latitude: float, longitude: float}
     */
    public static function place(int $index): array
    {
        $cityIndex = ($index - 1) % count(self::CITIES);
        $city = self::CITIES[$cityIndex];
        $district = self::DISTRICTS[$cityIndex][($index - 1) % count(self::DISTRICTS[$cityIndex])];
        $coords = AdSeederProfile::coordinates($index);

        return [
            'location' => $city.', '.$district,
            'latitude' => $coords[0] + (($index % 17) - 8) * 0.0012,
            'longitude' => $coords[1] + (($index % 13) - 6) * 0.0015,
        ];
    }

    /**
     * @return list<array{
     *     title: string,
     *     description: string,
     *     price: int|null,
     *     is_negotiable: bool,
     *     condition: string|null,
     *     delivery_methods: list<string>,
     *     image_name: string
     * }>
     */
    private static function baseItemsFor(string $slug): array
    {
        if ($slug === 'samochody') {
            return self::cars();
        }

        return self::fromTemplates($slug);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function fromTemplates(string $slug): array
    {
        $templates = DemoMarketplaceTemplates::for($slug);

        if ($templates === []) {
            return self::fallbackItems($slug);
        }

        $items = [];
        $target = 20;

        for ($offset = 0; $offset < $target; $offset++) {
            $items[] = self::buildFromTemplate($templates[$offset % count($templates)], $slug, $offset);
        }

        return $items;
    }

    /**
     * @param  array{
     *     title: string,
     *     description: string,
     *     price: int|null,
     *     image_name: string,
     *     kind?: string,
     *     condition?: string|null,
     *     negotiable?: bool,
     *     delivery?: list<string>
     * }  $template
     * @return array<string, mixed>
     */
    private static function buildFromTemplate(array $template, string $slug, int $offset): array
    {
        $basePrice = $template['price'];
        $price = $basePrice === null
            ? null
            : max(1, $basePrice + (($offset * 113) % 400) - 80);
        $kind = $template['kind'] ?? 'goods';

        if ($kind === 'service' || self::isJobCategory($slug) || self::isRealEstateCategory($slug)) {
            return self::service(
                $template['title'],
                $template['description'],
                $price,
                $template['image_name'],
                $template['delivery'] ?? [],
                $template['negotiable'] ?? true,
            );
        }

        return self::goods(
            $template['title'],
            $template['description'],
            $price,
            $template['image_name'],
            $template['delivery'] ?? ['personal', 'parcel_locker', 'courier'],
            $template['condition'] ?? null,
            $template['negotiable'] ?? true,
        );
    }

    /**
     * @param  array{
     *     title: string,
     *     description: string,
     *     price: int|null,
     *     is_negotiable: bool,
     *     condition: string|null,
     *     delivery_methods: list<string>,
     *     image_name: string
     * }  $base
     * @return array{
     *     title: string,
     *     description: string,
     *     price: string|null,
     *     is_negotiable: bool,
     *     condition: string|null,
     *     delivery_methods: list<string>,
     *     delivery_prices: array<string, string>,
     *     image_name: string
     * }
     */
    private static function varyListing(array $base, string $categorySlug, int $index): array
    {
        $variant = intdiv($index - 1, 20);
        $price = $base['price'];

        if ($price !== null) {
            $jitter = (($index * 17 + $variant * 431) % 11) - 5;
            $price = max(1, (int) round($price * (1 + $jitter / 100)));
        }

        $title = $base['title'];
        $description = $base['description'];

        if ($variant > 0) {
            $suffix = self::TITLE_SUFFIXES[$variant % count(self::TITLE_SUFFIXES)];
            if (! str_contains(mb_strtolower($title), mb_strtolower($suffix))) {
                $title .= $suffix;
            }

            $extra = self::DESCRIPTION_EXTRAS[($index + $variant) % count(self::DESCRIPTION_EXTRAS)];
            $description .= "\n\n".$extra;
        }

        if (self::isServiceCategory($categorySlug) || self::isJobCategory($categorySlug) || self::isRealEstateCategory($categorySlug)) {
            $description .= "\n\nKontakt wyłącznie przez wiadomości na portalu — proszę o krótki opis potrzeb.";
        }

        return [
            'title' => $title,
            'description' => $description,
            'price' => $price !== null ? self::money($price) : null,
            'is_negotiable' => $base['is_negotiable'] || $index % 5 === 0,
            'condition' => $base['condition'],
            'delivery_methods' => $base['delivery_methods'],
            'delivery_prices' => self::deliveryPrices($base['delivery_methods'], $index),
            'image_name' => DemoMarketplaceImageStore::normalizeImageName($base['image_name']),
        ];
    }

    /**
     * @param  list<string>  $methods
     * @return array<string, string>
     */
    private static function deliveryPrices(array $methods, int $index): array
    {
        $prices = [];

        foreach ($methods as $offset => $method) {
            $prices[$method] = $method === 'personal'
                ? '0.00'
                : self::money(12 + (($index + $offset * 3) % 18));
        }

        return $prices;
    }

    private static function isServiceCategory(string $slug): bool
    {
        return in_array($slug, [
            'serwis-i-naprawa', 'budowlane', 'transportowe', 'sprzatanie',
            'nauka-i-korepetycje', 'naprawy', 'pozostale-uslugi',
        ], true);
    }

    private static function isJobCategory(string $slug): bool
    {
        return in_array($slug, ['oferty-pracy', 'szukam-pracy', 'freelance', 'praktyki-i-staze'], true);
    }

    private static function isRealEstateCategory(string $slug): bool
    {
        return in_array($slug, [
            'mieszkania', 'domy', 'dzialki-i-grunty', 'lokale-i-biura', 'garaze-i-parkingi', 'pokoje-i-stancje',
        ], true);
    }

    private static function money(int $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private static function sellerBio(string $name, int $sequence): ?string
    {
        if ($sequence % 4 !== 0) {
            return null;
        }

        $bios = [
            'Sprzedaję rzeczy, których już nie używam — zawsze dodaję aktualne zdjęcia i odpowiadam wieczorami.',
            'Wolę kontakt przez wiadomości. Odbiór osobisty po wcześniejszym ustaleniu terminu.',
            'Na portalu od kilku lat — staram się opisywać stan uczciwie, bez ściemy.',
            'Wysyłam InPostem, dobrze pakuję. W razie pytań piszcie śmiało.',
        ];

        return $bios[($sequence + mb_strlen($name)) % count($bios)];
    }

    /**
     * @param  list<string>  $methods
     * @return array<string, mixed>
     */
    private static function goods(
        string $title,
        string $description,
        int $price,
        string $imageName,
        array $methods = ['personal', 'parcel_locker', 'courier'],
        ?string $condition = null,
        bool $negotiable = true,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'is_negotiable' => $negotiable,
            'condition' => $condition ?? AdCondition::Used->value,
            'delivery_methods' => $methods,
            'image_name' => $imageName,
        ];
    }

    /**
     * @param  list<string>  $methods
     * @return array<string, mixed>
     */
    private static function service(
        string $title,
        string $description,
        ?int $price,
        string $imageName,
        array $methods = [],
        bool $negotiable = true,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'is_negotiable' => $negotiable,
            'condition' => null,
            'delivery_methods' => $methods,
            'image_name' => $imageName,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function cars(): array
    {
        return [
            self::goods('Toyota Corolla 1.8 Hybrid 122 KM — 2019', "Auto kupione w salonie w Polsce, serwisowany w ASO co 15 tys. km.\n\nPrzebieg 74 tys. km, dwa kluczyki, klimatyzacja automatyczna, kamera cofania, tempomat adaptacyjny. Lakier oryginalny, bez rys na progach.\n\nOstatni przegląd zrobiony w marcu. Możliwy odbiór po umówieniu jazdy próbnej.", 72900, 'toyota-corolla.jpg'),
            self::goods('Skoda Octavia 2.0 TDI 150 KM DSG — kombi', "Wersja Style, rocznik 2018, przebieg 142 tys. km.\n\nWyposażenie: skórzana tapicerka, nagłośnienie Canton, reflektory Full LED, hak holowniczy składany. Wymienione tarcze i klocki z przodu w zeszłym roku.\n\nSprzedaję, bo przechodzę na auto hybrydowe. Faktura VAT-marża.", 48900, 'skoda-octavia.jpg'),
            self::goods('Volkswagen Golf 1.5 TSI 130 KM — Highline', "Golf VIII, benzyna, manualna skrzynia. Przebieg 58 tys. km, garażowany, jeden właściciel.\n\nPakiet Highline: Digital Cockpit, App-Connect, czujniki parkowania przód/tył, asystent pasa ruchu. Opony letnie Continental 2024.\n\nStan bardzo dobry, wnętrze bez przetarć.", 79900, 'vw-golf.jpg'),
            self::goods('BMW 320d Touring xDrive — M Pakiet', "Kombi 190 KM, automat, napęd na cztery koła. Rocznik 2017, przebieg 168 tys. km — głównie trasy.\n\nM Pakiet zewnętrzny, sportowe fotele, nawigacja Professional, podgrzewane fotele. Komplet historii serwisowej w książce.\n\nDrobna rysa parkingowa na zderzaku — widoczna na zdjęciu.", 84900, 'bmw-320d.jpg'),
            self::goods('Ford Focus 1.0 EcoBoost 125 KM', "Hatchback, manual, rocznik 2020. Przebieg 61 tys. km, pierwszy właściciel, używany do dojazdów do pracy.\n\nKlimatyzacja, Bluetooth, kamera cofania, system Ford SYNC. Świeży przegląd i OC do końca roku.\n\nCena do rozsądnej negocjacji przy szybkiej transakcji.", 46900, 'ford-focus.jpg'),
            self::goods('Audi A4 Avant 2.0 TDI 190 KM S tronic', "S line, automat, przebieg 121 tys. km. Wymienione łańcuch rozrządu przy 110 tys. km.\n\nWirtualny kokpit, światła Matrix LED, fotele Alcantara, elektryczna klapa bagażnika. Dwa komplety felg — letnie i zimowe na alufelgach.\n\nAuto gotowe do jazdy, bez ukrytych usterek.", 89900, 'audi-a4.jpg'),
            self::goods('Hyundai i30 1.6 CRDi — kombi', "Rocznik 2019, przebieg 95 tys. km. Wersja Premium: podgrzewane fotele, klimatyzacja dwustrefowa, Android Auto.\n\nBagażnik bardzo pojemny — idealny na rodzinne wyjazdy. Bezwypadkowy, lakier oryginalny.\n\nMożliwa zamiana na tańsze auto z dopłatą.", 52900, 'hyundai-i30.jpg'),
            self::goods('Kia Ceed 1.4 T-GDI GT Line', "Benzyna 140 KM, manual, rocznik 2021. Przebieg 44 tys. km.\n\nSportowe zawieszenie, felgi 17\", jasny pakiet asystentów, podgrzewana kierownica. Gwarancja fabryczna jeszcze przez rok.\n\nSprzedaję z powodu przeprowadzki za granicę.", 68900, 'kia-ceed.jpg'),
            self::goods('Mazda 3 2.0 Skyactiv-G 122 KM', "Sedan, automat, 2020 r. Przebieg 52 tys. km. Kolor Soul Red, bardzo ładnie utrzymany.\n\nBose, head-up display, skórzana tapicerka, czujniki martwego pola. Serwisowany w autoryzowanym serwisie Mazda.\n\nOdbiór możliwy w weekend.", 74900, 'mazda-3.jpg'),
            self::goods('Opel Astra 1.5 CDTI — Sports Tourer', "Kombi 2020, 105 KM, manual. Przebieg 88 tys. km.\n\nKlimatyzacja, tempomat, kamera cofania, relingi dachowe. Tanie w utrzymaniu, spalanie ok. 5 l/100 km w trasie.\n\nWszystkie wymiany oleju na bieżąco — książka serwisowa uzupełniona.", 42900, 'opel-astra.jpg'),
            self::goods('Renault Clio V 1.0 TCe 90 KM', "Małe miejskie auto, rocznik 2022, przebieg 28 tys. km. Idealne na miasto i parkowanie.\n\nMultimedia z CarPlay, klimatyzacja, czujniki cofania. Garażowane, niepalone w środku.\n\nPierwsza rejestracja w Polsce.", 54900, 'renault-clio.jpg'),
            self::goods('Peugeot 308 1.2 PureTech 130 KM', "Kombi 2021, automat EAT8. Przebieg 47 tys. km.\n\ni-Cockpit, światła LED, podgrzewane fotele, asystent parkowania. Pełna historia w książce serwisowej.\n\nSprzedaję po leasingu — auto sprawne w 100%.", 64900, 'peugeot-308.jpg'),
            self::goods('Seat Leon 1.5 TSI 150 KM FR', "Hatchback FR, DSG, 2019 r. Przebieg 79 tys. km.\n\nSportowe zawieszenie, felgi 18\", digital cockpit, bezkluczykowy dostęp. Nowe opony letnie.\n\nStan techniczny bez zastrzeżeń — ostatni serwis 2 tygodnie temu.", 59900, 'seat-leon.jpg'),
            self::goods('Toyota Yaris 1.5 Hybrid 116 KM', "Miejskie hybrydowe, 2021 r., przebieg 33 tys. km. Spalanie w mieście ok. 4 l.\n\nKamera cofania, lane assist, klimatyzacja auto. Gwarancja Toyota do 2026.\n\nBardzo ekonomiczne — polecam na dojazdy.", 62900, 'toyota-yaris.jpg'),
            self::goods('Volvo V60 D3 150 KM — Momentum', "Kombi 2018, automat, przebieg 134 tys. km. Bogate wyposażenie bezpieczeństwa Volvo.\n\nSkóra, nawigacja, podgrzewane siedzenia, hak. Regularnie serwisowany w niezależnym serwisie specjalizującym się w Volvo.\n\nDwa kluczyki, komplet dokumentów.", 76900, 'volvo-v60.jpg'),
            self::goods('Citroën C4 Cactus 1.2 PureTech', "Kompaktowy crossover, 2019, manual. Przebieg 72 tys. km.\n\nCharakterystyczne boczne paski Airbump, klimatyzacja, Bluetooth. Tanie ubezpieczenie i niskie spalanie.\n\nIdealny jako pierwsze auto.", 38900, 'citroen-c4.jpg'),
            self::goods('Nissan Qashqai 1.3 DIG-T 140 KM', "SUV, rocznik 2020, przebieg 66 tys. km. Wersja N-Connecta: kamera 360°, podgrzewane fotele.\n\nRodzinny, bezwypadkowy, regularnie myty i konserwowany. Dwa komplety opon.\n\nMożliwy transport lawetą w promieniu 100 km.", 82900, 'nissan-qashqai.jpg'),
            self::goods('Honda Civic 1.5 VTEC Turbo', "Sedan sportowy, 2018, manual 182 KM. Przebieg 98 tys. km.\n\nSportowy wydech, zawieszenie niskie, felgi 18\". Auto dynamiczne, zadbane mechanicznie.\n\nDla kogoś, kto lubi jeździć — nie dla oszczędności.", 71900, 'honda-civic.jpg'),
            self::goods('Suzuki Vitara 1.4 Boosterjet 4x4', "SUV z napędem na wszystkie koła, 2019, przebieg 81 tys. km.\n\nKlimatyzacja, kamera cofania, relingi, hak. Sprawdzony w górach — napęd działa bez zarzutu.\n\nOdbiór po wcześniejszym kontakcie.", 67900, 'suzuki-vitara.jpg'),
            self::goods('Dacia Duster 1.5 dCi 115 KM 4x4', "Praktyczny SUV, 2020, przebieg 89 tys. km. Wersja Prestige z klimatyzacją i kamerą.\n\nProste, tanie w naprawie, dobre na polskie drogi. Lekkie otarcie zderzaka — kosmetyka, nie wypadek.\n\nCena adekwatna do stanu.", 44900, 'dacia-duster.jpg'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function fallbackItems(string $slug): array
    {
        $label = str_replace('-', ' ', $slug);

        $imageName = $slug.'-oferta.jpg';

        return [
            self::goods(
                'Sprzedaż — '.$label,
                "Oferta prywatna w kategorii {$label}.\n\nPrzedmiot opisany szczegółowo w wiadomości, możliwy odbiór osobisty po umówieniu. Proszę o kontakt przez wiadomości — odpowiadam zwykle tego samego dnia.",
                199 + (crc32($slug) % 500),
                $imageName,
            ),
        ];
    }

    /** @var list<string> */
    private const array CITIES = [
        'Warszawa', 'Kraków', 'Wrocław', 'Poznań', 'Gdańsk', 'Łódź', 'Katowice',
        'Lublin', 'Szczecin', 'Bydgoszcz', 'Białystok', 'Rzeszów', 'Toruń', 'Kielce',
    ];

    /** @var list<list<string>> */
    private const array DISTRICTS = [
        ['Mokotów', 'Wola', 'Ursynów', 'Żoliborz', 'Praga-Południe'],
        ['Krowodrza', 'Podgórze', 'Nowa Huta', 'Bronowice', 'Dębniki'],
        ['Krzyki', 'Fabryczna', 'Psie Pole', 'Stare Miasto', 'Ołtaszyn'],
        ['Jeżyce', 'Grunwald', 'Wilda', 'Rataje', 'Winogrady'],
        ['Wrzeszcz', 'Oliwa', 'Przymorze', 'Chełm', 'Orunia'],
        ['Bałuty', 'Górna', 'Polesie', 'Widzew', 'Śródmieście'],
        ['Bogucice', 'Ligota', 'Zawodzie', 'Dąb', 'Koszutka'],
        ['Czuby', 'Sławin', 'Wieniawa', 'Dziesiąta', 'Tatary'],
        ['Pogodno', 'Niebuszewo', 'Centrum', 'Gumieńce', 'Dąbie'],
        ['Fordon', 'Błonie', 'Kapuściska', 'Wyżyny', 'Jachcice'],
        ['Centrum', 'Białostoczek', 'Wygoda', 'Antoniuk', 'Piaski'],
        ['Nowa Wieś', 'Północ', 'Drabinianka', 'Staromieście', 'Słocina'],
        ['Mokre', 'Bydgoskie Przedmieście', 'Rubinkowo', 'Koniuchy', 'Podgórz'],
        ['Barwinek', 'Herbskie', 'KSM', 'Szydłówek', 'Chęciny'],
    ];

    /** @var list<string> */
    private const array TITLE_SUFFIXES = [
        ' — stan bardzo dobry',
        ' — do negocjacji',
        ' — szybka transakcja',
        ' — odbiór osobisty',
        ' — aktualne zdjęcie',
    ];

    /** @var list<string> */
    private const array DESCRIPTION_EXTRAS = [
        'Możliwy odbiór po 17:00 w dni robocze.',
        'Wysyłka InPost po przedpłacie — dobrze zabezpieczam przesyłkę.',
        'Chętnie odpowiem na dodatkowe pytania i prześlę więcej zdjęć.',
        'Rezerwuję tylko po wpłacie zaliczki — bez zaliczki nie odkładam.',
        'Zainteresowanych proszę o konkretną propozycję terminu odbioru.',
    ];

    /** @var list<string> */
    private const array SELLER_NAMES = [
        'Anna Kowalska', 'Marek Nowak', 'Kasia z Mokotowa', 'Tomek_Sprzęt', 'Magda Wiśniewska',
        'Piotr Zieliński', 'Ola_Dom_i_Ogród', 'fotograf_marta', 'Krzysztof Dąbrowski', 'Ewelina Lewandowska',
        'rowerzysta_krk', 'Michał Wójcik', 'Agnieszka Kamińska', 'marcin_elektronik', 'Joanna Szymańska',
        'Paweł Woźniak', 'natalia_moda', 'Robert Mazur', 'Karolina Król', 'agd_tomek',
        'Łukasz Jankowski', 'Beata Krawczyk', 'studio_foto_pawel', 'Damian Piotrowski', 'Monika Grabowska',
        'Szymon Nowicki', 'Paulina Pawłowska', 'meble_z_warszawy', 'Rafał Michalski', 'Weronika Zając',
        'Hubert Krupa', 'Izabela Jasińska', 'korepetycje_ania', 'Grzegorz Adamczyk', 'Sylwia Wieczorek',
        'Mateusz Dudek', 'Renata Majewska', 'transport_marek', 'Artur Olszewski', 'Justyna Stępień',
        'Kamil Malinowski', 'Dorota Pawlak', 'zabawki_dla_malucha', 'Bartosz Rutkowski', 'Alicja Sikora',
        'Dominik Baran', 'remont_master', 'Patrycja Górska', 'Sebastian Ostrowski', 'Wiktoria Duda',
    ];
}
