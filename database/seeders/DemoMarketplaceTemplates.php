<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdCondition;

/**
 * Szablony ogłoszeń per kategoria — krótkie, realistyczne oferty po polsku.
 */
final class DemoMarketplaceTemplates
{
    /**
     * @return list<array{
     *     title: string,
     *     description: string,
     *     price: int,
     *     image: string,
     *     image_name: string,
     *     kind?: string,
     *     condition?: string|null,
     *     negotiable?: bool,
     *     delivery?: list<string>
     * }>
     */
    /** @var array<string, list<array<string, mixed>>>|null */
    private static ?array $data = null;

    /**
     * @return list<array<string, mixed>>
     */
    public static function for(string $slug): array
    {
        if (isset(self::data()[$slug])) {
            return self::data()[$slug];
        }

        return self::generated($slug);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private static function data(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        self::$data = self::buildData();

        return self::$data;
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private static function buildData(): array
    {
        return [
            'motocykle-i-skutery' => [
                self::goods('Yamaha MT-07 2019 — ABS, quickshifter', "Motocykl kupiony w salonie, przebieg 18 tys. km.\n\nNowe opony, komplet dokumentów, kufry boczne w zestawie. Używany głównie na weekendowe trasy, garażowany zimą.\n\nMożliwa jazda próbna po wcześniejszym umówieniu.", 28900, 'yamaha-mt07.jpg'),
                self::goods('Honda PCX 125 — miejski skuter', "Skuter idealny do miasta, rocznik 2021, przebieg 9 tys. km.\n\nKask i kurtka w rozmiarze M mogę dorzucić za dopłatą. Bagażnik mieści kask integralny.\n\nOC ważne, przegląd świeży.", 11900, 'honda-pcx.jpg'),
                self::goods('BMW R 1250 GS Adventure', "Motocykl turystyczny, 2020, przebieg 34 tys. km.\n\nPakiet touring, podgrzewane manetki, kufry oryginalne BMW. Serwisowany w autoryzowanym serwisie.\n\nSprzedaję z powodu przejścia na mniejszy pojazd.", 64900, 'bmw-gs.jpg'),
                self::goods('Vespa Primavera 50 4T', "Klasyczny skuter, 2018, przebieg 6 tys. km.\n\nIdealny na krótkie dojazdy, bardzo ekonomiczny. Lakier biały, stan wizualny bardzo dobry.\n\nOdbiór osobisty.", 8900, 'vespa.jpg'),
                self::goods('KTM Duke 390 — pierwszy właściciel', "Naked bike, 2022, przebieg 4 tys. km.\n\nABS, tryby jazdy, TFT. Sprzedaję, bo przesiadam się na auto.\n\nFaktura zakupu do wglądu.", 21900, 'ktm-duke.jpg'),
            ],
            'opony-i-felgi' => [
                self::goods('Felgi aluminiowe BBS SR 17" 5x112', "Cztery felgi po sezonie letnim, lekkie otarcia na krawędziach widoczne na zdjęciu.\n\nPasują m.in. do VW, Audi, Skody. Bez opon — same felgi.\n\nOdbiór lub wysyłka po uzgodnieniu.", 1499, 'felgi.jpg'),
                self::goods('Opony zimowe Michelin Alpin 205/55 R16', "Komplet używany jeden sezon, bieżnik ok. 6 mm.\n\nPrzechowywane w workach, bez pęknięć i łat. Data produkcji 2022.\n\nMożliwy montaż u mnie za dodatkową opłatą.", 899, 'opony-zimowe.jpg'),
                self::goods('Koła zapasowe stalowe 15" 4x100', "Dwa koła z oponami letnimi — bieżnik do jazdy sezonowej.\n\nIdealne jako zapas lub na auto młodzieżowe.\n\nCena za komplet dwóch kół.", 450, 'kola-stalowe.jpg'),
                self::goods('Opony letnie Continental 225/45 R17', "Komplet czterech opon, rok 2023, przejechane ok. 12 tys. km.\n\nRównomierne zużycie, bez uszkodzeń bocznych.\n\nWysyłka InPost po przedpłacie.", 1299, 'opony-letnie.jpg'),
                self::goods('Felgi BMW 18" style 397 — oryginał', "Komplet alufelg z serii 3/4, drobne ślady użytkowania.\n\nSprawdzona geometria, bez spawów i prostowania.\n\nCena do rozsądnej negocjacji.", 3200, 'felgi-bmw.jpg'),
            ],
            'oleje-i-plyny' => [
                self::goods('Olej silnikowy Castrol Edge 5W-30 — 5 l', "Pełna beczka, zakupiona w sklepie motoryzacyjnym, nieużywana.\n\nTermin ważności 2027. Pasuje do wielu silników benzynowych i diesla.\n\nWysyłka lub odbiór.", 189, 'olej-castrol.jpg'),
                self::goods('Płyn hamulcowy DOT 4 — 2 butelki', "Oryginalne opakowania, kupione na zapas — sprzedaję nadmiar.\n\nNieotwierane, przechowywane w suchym miejscu.\n\nCena za obie butelki.", 49, 'plyn-hamulcowy.jpg'),
                self::goods('Płyn do spryskiwaczy koncentrat 5 l', "Koncentrat na zimę, -20°C. Zostało po serwisie floty firmowej.\n\nWydajny — wystarcza na długi sezon.\n\nOdbiór osobisty.", 35, 'spryskiwacz.jpg'),
                self::goods('Olej do skrzyni automatycznej ATF', "Litruje ok. 4 l, specyfikacja zgodna z wieloma automatami.\n\nKupiony pod wymianę, zostało po serwisie.\n\nProszę sprawdzić zgodność ze swoim modelem.", 120, 'atf.jpg'),
                self::goods('AdBlue 10 l — oryginalne opakowanie', "Nieużywane, zakupione do diesla, auto sprzedane wcześniej.\n\nPełna kanistra, termin ważności na etykiecie.\n\nMożliwa wysyłka.", 65, 'adblue.jpg'),
            ],
            'czesci-karoserii' => [
                self::goods('Zderzak przedni VW Golf VII — lakierowany', "Zderzak po delikatnej szkodzie parkingowej, naprawiony i lakierowany w lakierni.\n\nKolor biały, odcień bardzo zbliżony do oryginału.\n\nBez montażu — odbiór osobisty.", 650, 'zderzak.jpg'),
                self::goods('Drzwi lewe przednie Opel Astra K', "Drzwi kompletne z szybą i mechanizmem, kolor szary.\n\nBez korozji, zaczepy sprawne. Idealne na wymianę po stłuczce.\n\nProszę o kontakt przez wiadomości.", 890, 'drzwi.jpg'),
                self::goods('Maska silnika Skoda Octavia III', "Oryginalna maska, kolor czarny metalik, lekkie odpryski od kamieni.\n\nBez wgnieceń, zawiasy w komplecie.\n\nMożliwy transport na palecie.", 750, 'maska.jpg'),
                self::goods('Błotnik przedni prawy Ford Focus', "Część z demontażu, stan dobry, drobne rysy parkingowe.\n\nNumer części do wglądu po kontakcie.\n\nCena do negocjacji przy szybkim odbiorze.", 280, 'blotnik.jpg'),
                self::goods('Klapa bagażnika Audi A4 B9', "Kolor szary, czujniki parkowania w komplecie.\n\nSprawna elektryczna blokada, bez korozji.\n\nOdbiór po umówieniu.", 1450, 'klapa.jpg'),
            ],
            'dostawcze-i-ciezarowe' => [
                self::goods('Ford Transit Custom L2H1 — 2.0 TDCi', "Bus dostawczy, 2019, przebieg 156 tys. km.\n\nKlima, tempomat, drzwi boczne przesuwne. Wnętrze czyste, regularnie serwisowany.\n\nFaktura VAT-marża.", 69900, 'transit.jpg'),
                self::goods('Mercedes Sprinter 316 CDI', "Rocznik 2018, długa wersja, przebieg 198 tys. km.\n\nIdealny pod transport palet lub wyposażenie warsztatowe.\n\nOstatni serwis olejowy 3 tys. km temu.", 84900, 'sprinter.jpg'),
                self::goods('Iveco Daily 35S14 skrzynia', "Ciężarówka 3,5 t, 2017, przebieg 241 tys. km.\n\nŁadownia czysta, hak, tempomat. Używana w firmie kurierskiej.\n\nMożliwy przegląd w serwisie przed zakupem.", 52900, 'iveco.jpg'),
                self::goods('Renault Master L3H2 — chłodnia', "Bus z agregatem chłodniczym, rocznik 2020.\n\nSprawny układ chłodzenia, regularne przeglądy. Idealny pod catering.\n\nCena netto do omówienia.", 98900, 'master.jpg'),
                self::goods('Volkswagen Crafter — wywrotka', "Samochód użytkowy z wywrotką, 2016, przebieg 312 tys. km.\n\nMechanicznie sprawny, regularnie użytkowany na budowach.\n\nOdbiór po wcześniejszym kontakcie.", 45900, 'crafter.jpg'),
            ],
            'przyczepy-i-naczepy' => [
                self::goods('Przyczepa lekka 750 kg — plandeka', "Przyczepa jednoosiowa, rok 2021, mało używana.\n\nHamulec najazdowy, oświetlenie LED, podłoga sklejka.\n\nIdealna pod przeprowadzkę lub transport mebli.", 8900, 'przyczepa.jpg'),
                self::goods('Laweta samochodowa 2700 kg', "Laweta do transportu aut, hamulce sprawne, nowe opony.\n\nUżywana sporadycznie, zadbana.\n\nMożliwy transport do kupującego za dodatkową opłatą.", 18900, 'laweta.jpg'),
                self::goods('Przyczepa kempingowa mała 2 osoby', "Kempingowa, rozkładana, stan dobry.\n\nW środku miejsce na spanie i prosty aneks. Idealna na krótkie wyjazdy.\n\nOdbiór osobisty — proszę o kontakt.", 12900, 'kempingowa.jpg'),
                self::goods('Przyczepa rolnicza do siana', "Rozkładana, sprawna, gotowa do pracy.\n\nBlacha bez dziur, oświetlenie kompletne.\n\nCena do negocjacji.", 14900, 'rolnicza.jpg'),
                self::goods('Naczepa chłodnicza — agregat Carrier', "Naczepa pod ciągnik, rok 2015, regularny serwis agregatu.\n\nWymiary wewnętrzne do podania po kontakcie.\n\nTylko odbiór własny.", 74900, 'naczepa.jpg'),
            ],
            'serwis-i-naprawa' => [
                self::service('Mechanika samochodowa — szybka diagnostyka', "Prywatny warsztat, specjalizacja w autach japońskich i koreańskich.\n\nDiagnostyka komputerowa, wymiany oleju, hamulców, zawieszenia. Terminy zwykle w ciągu 2–3 dni.\n\nWycena po krótkim opisie problemu w wiadomości.", 150, 'warsztat.jpg'),
                self::service('Wulkanizacja mobilna — dojazd', "Dojadę w promieniu 20 km, naprawa przebicia lub wymiana koła.\n\nPracuję popołudniami i w weekendy.\n\nProszę podać rozmiar opony i lokalizację.", 80, 'wulkanizacja.jpg'),
                self::service('Geometria i zbieżność kół', "Stanowisko 3D, obsługa aut osobowych i dostawczych.\n\nPo wymianie zawieszenia lub przed sezonem letnim warto sprawdzić ustawienie.\n\nUmówienie po wcześniejszym kontakcie.", 120, 'geometria.jpg'),
                self::service('Naprawa klimatyzacji samochodowej', "Odgrzybianie, uzupełnienie czynnika, szczelność układu.\n\nObsługuję większość marek, pracuję na oryginalnych czynnikach.\n\nCzas realizacji zwykle tego samego dnia.", 199, 'klima.jpg'),
                self::service('Tuning i montaż akcesoriów', "Montaż haków, kamer, alarmów, podświetleń.\n\nDoświadczenie 12 lat, faktura na życzenie.\n\nWycena indywidualna po opisie auta.", 250, 'tuning.jpg'),
            ],
            'laptopy' => [
                self::goods('MacBook Air M2 8/256 GB — Midnight', "Laptop kupiony w 2023, używany do pracy biurowej.\n\nBateria ok. 92%, bez rys na ekranie, oryginalna ładowarka w zestawie.\n\niCloud wylogowany, gotowy do pracy.", 3899, 'macbook-air.jpg'),
                self::goods('Lenovo ThinkPad T14 Gen 3 — i5, 16 GB', "Laptop firmowy po zwrocie z lease, profesjonalnie przygotowany.\n\n512 GB SSD, Windows 11 Pro, klawiatura PL z podświetleniem.\n\nIdealny do pracy zdalnej.", 2799, 'thinkpad.jpg'),
                self::goods('Dell XPS 15 — OLED, RTX 3050', "Maszyna do grafiki i montażu wideo, rocznik 2022.\n\n32 GB RAM, ekran OLED bez wypaleń. Sprzedaję po przejściu na stacjonarkę.\n\nMożliwa wysyłka dobrze zabezpieczona.", 5499, 'dell-xps.jpg'),
                self::goods('ASUS ROG Zephyrus G14 — ryzen, 16 GB', "Laptop gamingowy, chłodzenie sprawne, temperatury w normie.\n\nUżywany głównie w domu, stan bardzo dobry.\n\nW zestawie oryginalny zasilacz 240 W.", 4299, 'asus-rog.jpg'),
                self::goods('HP EliteBook 840 G8 — lekki ultrabook', "14\", i7, 16 GB RAM, 512 GB SSD.\n\nIdealny na podróże służbowe, bateria trzyma ok. 6–7 h.\n\nOdbiór po umówieniu.", 2499, 'elitebook.jpg'),
            ],
            'telefony' => [
                self::goods('iPhone 14 Pro 256 GB — Deep Purple', "Telefon z polskiej dystrybucji, bateria 89%.\n\nEkran bez rys, Face ID sprawne, w zestawie pudełko i kabel.\n\nBez blokady operatora.", 3299, 'iphone-14.jpg'),
                self::goods('Samsung Galaxy S23 Ultra 512 GB', "Flagowiec w bardzo dobrym stanie, używany rok.\n\nS Pen, aparaty bez problemów, szkło ochronne od nowości.\n\nMożliwa wysyłka InPost.", 3599, 's23-ultra.jpg'),
                self::goods('Google Pixel 8 — czysty Android', "Telefon 2023, aktualizacje do 2030.\n\nAparat świetny na zdjęcia dzienne, bateria 91%.\n\nW zestawie etui Spigen.", 2199, 'pixel-8.jpg'),
                self::goods('Xiaomi 13T Pro — 12/512 GB', "Szybkie ładowanie 120 W, ekran AMOLED 144 Hz.\n\nStan bardzo dobry, bez zarysowań na ramce.\n\nOdblokowany, działa w każdej sieci.", 1899, 'xiaomi-13t.jpg'),
                self::goods('iPhone SE 2022 128 GB — kompakt', "Mały, poręczny telefon, idealny jako drugi lub dla seniora.\n\nBateria 86%, Touch ID sprawny.\n\nCena do negocjacji.", 999, 'iphone-se.jpg'),
            ],
            'rowery' => [
                self::goods('Trek Domane AL 2 — kolarstwo szosowe', "Rower szosowy, rozmiar 56, rok 2021.\n\nPrzebieg ok. 2500 km, regularnie serwisowany. Idealny na dłuższe trasy i amatorskie wyścigi.\n\nMożliwy test jazdy.", 3499, 'trek-domane.jpg'),
                self::goods('Giant Talon 29 — MTB', "Górski, amortyzator przedni, rozmiar L.\n\nŁańcuch i kaseta wymienione w tym sezonie.\n\nSprzedaję, bo kupuję e-bike.", 2299, 'giant-talon.jpg'),
                self::goods('Kross Level 6.0 — trekking', "Rower trekkingowy z bagażnikiem i błotnikami.\n\nKomfortowa geometria, shimano deore, idealny na dojazdy.\n\nOdbiór osobisty.", 1899, 'kross-level.jpg'),
                self::goods('Rower miejski damski — 28 cali', "Wygodna rama, 7 biegów, koszyk z przodu.\n\nUżywany do spokojnych przejazdów po parku.\n\nCena symboliczna, bo robię miejsce w garażu.", 650, 'rower-miejski.jpg'),
                self::goods('Scott Speedster Gravel 30', "Gravel na szuter i asfalt, rozmiar 54.\n\nSzerokie opony, hamulce tarczowe mechaniczne.\n\nStan bardzo dobry.", 3999, 'scott-gravel.jpg'),
            ],
            'mieszkania' => [
                self::service('Mieszkanie 3 pokoje — remont 2024, metro 8 min', "Na sprzedaż mieszkanie 62 m² z osobną kuchnią i balkonem.\n\nŁazienka po generalnym remoncie, nowe okna PCV, miejsce postojowe w garażu podziemnym w cenie.\n\nWolne od zaraz, możliwe oględziny w weekend.", 749000, 'mieszkanie.jpg'),
                self::service('Kawalerka 32 m² — inwestycja pod wynajem', "Kawalerka w nowym budownictwie, w pełni umeblowana.\n\nNajem krótkoterminowy przynosi stabilny dochód — mogę podzielić się doświadczeniem z wynajmu.\n\nCena zawiera miejsce w hali.", 429000, 'kawalerka.jpg'),
                self::service('Mieszkanie 4 pokoje — duży balkon', "Rodzinne 78 m², dwa poziomy, garderoba.\n\nSpokojna okolica, szkoła i przedszkole w pobliżu.\n\nZapraszam na prezentację po wcześniejszym umówieniu.", 899000, 'mieszkanie-4p.jpg'),
                self::service('Mieszkanie 2 pokoje — pierwsze własne', "Przestronny salon z aneksem, sypialnia z szafą w zabudowie.\n\nNiskie czynsze, wspólnota bez zadłużenia.\n\nMożliwa szybka transakcja.", 559000, 'mieszkanie-2p.jpg'),
                self::service('Apartament 55 m² — widok na park', "Wysoki standard wykończenia, ogrzewanie miejskie.\n\nGaraż opcjonalnie, winda, monitoring.\n\nProszę o kontakt przez wiadomości.", 689000, 'apartament.jpg'),
            ],
            'oferty-pracy' => [
                self::service('Magazynier / kompletacja zamówień — pełny etat', "Firma e-commerce poszukuje osoby do pracy w magazynie.\n\nStawka od 4800 brutto, premie za nadgodziny, umowa o pracę po okresie próbnym.\n\nStart możliwy od zaraz, szkolenie w pierwszym tygodniu.", 4800, 'magazynier.jpg'),
                self::service('Specjalista ds. obsługi klienta — zdalnie hybrydowo', "Praca w języku polskim i angielskim, system ticketowy, elastyczne godziny.\n\nDoświadczenie mile widziane, ale szkolimy od podstaw.\n\nWyślij krótkie CV w wiadomości.", 5500, 'obsluga-klienta.jpg'),
                self::service('Kierowca kat. C+E — trasy krajowe', "Transport międzynarodowy, nowe ciągniki, regularne trasy.\n\nDiety, noclegi, stabilne obłożenie.\n\nWymagane prawo jazdy C+E i kod 95.", 8500, 'kierowca.jpg'),
                self::service('Lakiernik samochodowy — pełny etat', "Warsztat blacharsko-lakierniczy, nowoczesna kabina.\n\nUmowa o pracę, narzędzia po stronie pracodawcy.\n\nZapraszamy osoby z doświadczeniem min. 2 lata.", 6200, 'lakiernik.jpg'),
                self::service('Programista PHP/Laravel — zdalnie', "Rozwój platformy ogłoszeniowej, zespół 6 osób, code review i testy obowiązkowe.\n\nB2B lub UoP, widełki do omówienia.\n\nProszę o link do GitHub lub portfolio.", 12000, 'programista.jpg'),
            ],
            'budowlane' => [
                self::service('Remont łazienki pod klucz', "Kompleksowe wykończenie: hydraulika, elektryka, glazura, armatura.\n\nDojazd na wycenę gratis w promieniu 30 km.\n\nTerminy od 3 tygodni, materiały po stronie klienta lub na fakturze.", 0, 'remont-lazienki.jpg'),
                self::service('Układanie płytek i gresu', "Doświadczenie 15 lat, równe fugi, odpowiednie przygotowanie podłoża.\n\nRealizuję łazienki, kuchnie, tarasy.\n\nWycena po pomiarze i wyborze formatu.", 120, 'plytki.jpg'),
                self::service('Ekipa budowlana — murowanie i tynki', "Ściany działowe, murowanie, tynki maszynowe.\n\nPracujemy na dokumentacji lub według ustalonych wymiarów.\n\nFaktura VAT.", 0, 'budowlana.jpg'),
                self::service('Montaż suchej zabudowy', "Ścianki GK, sufity podwieszane, wnęki LED.\n\nSzybka realizacja, sprzątanie po pracy.\n\nProszę o rzut lub zdjęcia pomieszczenia.", 85, 'gk.jpg'),
                self::service('Wylewki i posadzki betonowe', "Wylewka samopoziomująca, przygotowanie pod panele lub płytki.\n\nObsługuję mieszkania i domy.\n\nCena za m² do ustalenia po oględzinach.", 65, 'wylewka.jpg'),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function generated(string $slug): array
    {
        $specs = self::SPECS[$slug]
            ?? DemoMarketplaceCategorySpecs::all()[$slug]
            ?? null;

        if ($specs === null) {
            throw new \RuntimeException("Brak szablonów demo dla kategorii: {$slug}");
        }

        $items = [];

        foreach ($specs as $spec) {
            [$title, $description, $price, $name, $kind] = $spec;

            $items[] = $kind === 'service'
                ? self::service($title, $description, $price, $name)
                : self::goods($title, $description, $price, $name);
        }

        return $items;
    }

    /** @var array<string, list<array{0: string, 1: string, 2: int, 3: string, 4: string}>> */
    private const array SPECS = [
        'domy' => [
            ['Dom jednorodzinny 140 m² — działka 800 m²', "Dom murowany, rok 2015, ogrzewanie gazowe.\n\nTrzy sypialnie, salon z kominkiem, garaż dwustanowiskowy. Ogród z nasadzeniami, altana.\n\nSpokojna okolica, szkoła 5 min pieszo.", 890000, 'dom.jpg', 'service'],
            ['Dom szeregowy po remoncie', "Powierzchnia 110 m², nowe okna, docieplenie elewacji.\n\nDach wymieniony w 2023. Gotowy do zamieszkania bez dodatkowych nakładów.\n\nZapraszam na oględziny w weekend.", 649000, 'dom-szeregowy.jpg', 'service'],
            ['Dom z widokiem na las — pod Poznaniem', "Działka 1200 m², media w drodze.\n\nIdealny dla rodziny szukającej ciszy, 25 min do miasta.\n\nMożliwość adaptacji pod wynajem krótkoterminowy.", 720000, 'dom-las.jpg', 'service'],
            ['Dom parterowy z tarasem', "Bez barier architektonicznych, 95 m².\n\nŁazienka z prysznicem walk-in, ogrzewanie podłogowe.\n\nWysoki standard wykończenia.", 599000, 'dom-parterowy.jpg', 'service'],
            ['Dom do adaptacji — duży potencjał', "Stary dom z dużą działką, wymaga remontu.\n\nSolidne fundamenty, nowa wiata garażowa.\n\nCena odzwierciedla stan — do negocjacji.", 380000, 'dom-adaptacja.jpg', 'service'],
        ],
        'komponenty' => [
            ['Karta graficzna RTX 3070 8 GB', "Używana w domu, temperatury w normie, bez kopania.\n\nOryginalne opakowanie, stabilna w grach i renderze.\n\nMożliwa wysyłka dobrze zabezpieczona.", 1299, 'rtx-3070.jpg', 'goods'],
            ['Procesor AMD Ryzen 7 5800X', "Sprzedaję po upgrade na nowszą platformę.\n\nDziałał z chłodzeniem tower, nigdy nie był kręcony agresywnie.\n\nW zestawie oryginalne pudełko.", 699, 'ryzen.jpg', 'goods'],
            ['Płyta główna ASUS B550-F Gaming', "Socket AM4, PCIe 4.0, Wi-Fi 6.\n\nSprawna, BIOS zaktualizowany pod Zen 3.\n\nOdbiór lub wysyłka.", 449, 'plyta-glowna.jpg', 'goods'],
            ['RAM DDR4 32 GB (2x16) 3600 MHz', "Kit CL18, profil XMP działa poprawnie.\n\nUżywany rok w stacji roboczej.\n\nCena za komplet.", 299, 'ram.jpg', 'goods'],
            ['Dysk SSD NVMe 1 TB Samsung 980 Pro', "Szybki dysk systemowy, health 96%.\n\nSprzedaję nadmiar po rozbudowie NAS.\n\nMożliwa wysyłka InPost.", 349, 'ssd.jpg', 'goods'],
        ],
        'meble' => [
            ['Sofa narożna rozkładana — szary welur', "Duża sofa z pojemnikiem na pościel, bardzo wygodna.\n\nStan dobry, bez plam i przetarć. Wymiary do podania po kontakcie.\n\nOdbiór własny — proszę o pomoc przy załadunku.", 1899, 'sofa.jpg', 'goods'],
            ['Stół dębowy rozkładany 160–220 cm', "Solidny stół z litego dębu, olejowany.\n\nPasuje do jadalni 6–10 osób po rozłożeniu.\n\nMogę pomóc z demontażem nóg przy odbiorze.", 2499, 'stol-debowy.jpg', 'goods'],
            ['Komoda RTV z szufladami', "Nowoczesna, biała, lekko używana dwa lata.\n\nBez uszkodzeń, stabilna. Idealna pod telewizor do 65\".\n\nWysyłka niemożliwa — tylko odbiór.", 699, 'komoda-rtv.jpg', 'goods'],
            ['Łóżko 160x200 z pojemnikiem', "Rama tapicerowana, stelaż lamelowy w zestawie.\n\nMaterac sprzedaję osobno. Kolor beżowy.\n\nOdbiór po umówieniu.", 1199, 'lozko.jpg', 'goods'],
            ['Regał biblioteczny industrialny', "Metal i drewno, bardzo stabilny.\n\nIdealny do salonu lub biura domowego.\n\nRozbieralny do transportu.", 899, 'regal.jpg', 'goods'],
        ],
        'agd' => [
            ['Pralka Samsung EcoBubble 8 kg', "Używana 3 lata, 1400 obr/min, klasa A.\n\nProgram szybki 15 min, para, dodatkowe płukanie.\n\nOdbiór — proszę o własny transport.", 999, 'pralka.jpg', 'goods'],
            ['Lodówka LG side-by-side', "No Frost, kostkarka, wyświetlacz na drzwi.\n\nStan bardzo dobry, regularnie czyszczona.\n\nWymaga dwóch osób do transportu.", 2299, 'lodowka.jpg', 'goods'],
            ['Zmywarka Bosch 60 cm — cicha', "Używana w rodzinie 4-osobowej, 46 dB.\n\nProgram auto, szuflada na sztućce.\n\nSprzedaję po remoncie kuchni.", 1299, 'zmywarka.jpg', 'goods'],
            ['Ekspres kolbowy DeLonghi', "Mało używany, regularnie odkamieniany.\n\nMłynek wbudowany, spieniacz mleka.\n\nIdealny na poranną kawę.", 899, 'ekspres.jpg', 'goods'],
            ['Odkurzacz Dyson V11', "Bezprzewodowy, dwa akumulatory w zestawie.\n\nSzczotki do parkietu i dywanów, stacja ładowania.\n\nSprawny, moc ssania bez spadku.", 1199, 'dyson.jpg', 'goods'],
        ],
        'odziez-damska' => [
            ['Płaszcz wełniany Max Mara — rozmiar 38', "Klasyka, kolor camel, noszony jeden sezon.\n\nBez plam i dziur, profesjonalnie czyszczony.\n\nOryginalna metka zachowana.", 899, 'plaszcz.jpg', 'goods'],
            ['Sukienka wieczorowa — rozmiar M', "Ciemna zieleń, długość midi, raz założona na wesele.\n\nBardzo dobrej jakości tkanina, podszewka w komplecie.\n\nWysyłka możliwa.", 349, 'sukienka.jpg', 'goods'],
            ['Kurtka puchowa The North Face — S', "Lekka, ciepła, kaptur odczepiany.\n\nIdealna na góry i zimę w mieście.\n\nStan bardzo dobry.", 599, 'kurtka.jpg', 'goods'],
            ['Jeansy Levi\'s 501 — W28 L32', "Klasyczny krój, lekko przyciemnione od noszenia.\n\nBez przetarć na kolanach.\n\nCena do negocjacji.", 179, 'jeansy.jpg', 'goods'],
            ['Bluzka jedwabna — kolor ecru', "Elegancka, do pracy i na spotkania.\n\nRozmiar 36, delikatne pranie ręczne.\n\nBez przebarwień.", 129, 'bluzka.jpg', 'goods'],
        ],
        'zabawki' => [
            ['LEGO Technic 42054 Mercedes Actros', "Ciężarówka Technic, w oryginalnym pudełku, złożona i rozkładana raz.\n\nWszystkie elementy, instrukcja, brak braków.\n\nIdealny prezent.", 449, 'lego.jpg', 'goods'],
            ['Klocki magnetyczne Connetix 100 el.', "Rozwijają kreatywność, używane w domu.\n\nKomplet zgodny z listą, przechowywane w pojemniku.\n\nWysyłka InPost.", 299, 'klocki.jpg', 'goods'],
            ['Lalka Barbie Dreamhouse', "Duży domek, kilka akcesoriów w zestawie.\n\nDrobne ślady zabawy, bez uszkodzeń konstrukcyjnych.\n\nOdbiór osobisty.", 399, 'barbie.jpg', 'goods'],
            ['Hot Wheels — kolekcja 40 aut', "Oryginalne opakowania, modele z różnych serii.\n\nDla kolekcjonera lub na prezent.\n\nCena za całość.", 249, 'hot-wheels.jpg', 'goods'],
            ['Gra planszowa Dixit — polska wersja', "Karty w idealnym stanie, pudełko lekko przytarte.\n\nŚwietna na wieczory z przyjaciółmi.\n\nMożliwa wysyłka.", 89, 'dixit.jpg', 'goods'],
        ],
        'szukam-pracy' => [
            ['Szukam pracy jako grafik UI/UX', "3 lata doświadczenia w Figma, design systemach i prototypach.\n\nPortfolio i case studies wyślę po kontakcie.\n\nPreferuję pracę hybrydową w Krakowie lub zdalnie.", 0, 'grafik.jpg', 'service'],
            ['Kierowca kat. B — doświadczenie 8 lat', "Czysta karta, punktualny, znajomość Trójmiasta.\n\nSzukam pracy w transporcie lokalnym lub jako kurier.\n\nDyspozycyjny od zaraz.", 0, 'kierowca-b.jpg', 'service'],
            ['Księgowa — pełna obsługa JDG i spółek', "15 lat w biurze rachunkowym, Płatnik, KSeF.\n\nSzukam współpracy B2B lub etatu.\n\nPreferuję małe i średnie firmy.", 0, 'ksiegowa.jpg', 'service'],
            ['Opiekunka dzieci — referencje', "Doświadczenie z dziećmi 2–10 lat, pierwsza pomoc.\n\nElastyczne godziny, mogę dojazd.\n\nChętnie spotkam się na rozmowę.", 0, 'opiekunka.jpg', 'service'],
            ['Junior frontend developer — Vue', "Ukończone bootcamp, własne projekty w Vue 3 i TypeScript.\n\nSzukam pierwszej pracy lub stażu z mentorem.\n\nMogę pracować zdalnie.", 0, 'frontend.jpg', 'service'],
        ],
        'transportowe' => [
            ['Przeprowadzki krajowe — bus z windą', "Transport mieszkań i biur, zabezpieczenie mebli folią i kocami.\n\nDoświadczona dwuosobowa ekipa, terminowość.\n\nWycena po liście rzeczy i piętrze.", 0, 'przeprowadzki.jpg', 'service'],
            ['Transport paletowy — kraj cały', "Bus skrzyniowy, ubezpieczenie OCP, dokumenty WZ.\n\nRegularne kursy Kraków–Warszawa–Wrocław.\n\nFaktura VAT.", 0, 'transport.jpg', 'service'],
            ['Wynajem busa z kierowcą — 8 osób', "Wesela, delegacje, wycieczki.\n\nKomfortowy minibus, klimatyzacja.\n\nCena za km lub ryczałt do ustalenia.", 450, 'bus.jpg', 'service'],
            ['Transport pianin i sejfów', "Specjalistyczny sprzęt, szelki, osłony narożników.\n\nDoświadczenie w wąskich klatkach schodowych.\n\nWycena po zdjęciach i adresie.", 0, 'pianino.jpg', 'service'],
            ['Kurier lokalny — ekspres w mieście', "Dostawy dokumentów i małych paczek tego samego dnia.\n\nObsługuję centrum i dzielnice południowe.\n\nStała współpraca możliwa.", 35, 'kurier.jpg', 'service'],
        ],
        'naprawy' => [
            ['Naprawa laptopów i komputerów', "Diagnostyka, wymiana matryc, klawiatur, dysków, czyszczenie.\n\nSzybka wycena po opisie objawów.\n\nOdbiór i dostawa w mieście możliwa.", 0, 'laptop-naprawa.jpg', 'service'],
            ['Serwis pralek i zmywarek', "Doświadczenie 10 lat, oryginalne i zamienniki premium.\n\nDojazd do klienta, gwarancja na części.\n\nProszę podać markę i objawy.", 120, 'agd-serwis.jpg', 'service'],
            ['Naprawa rowerów — sezonowa', "Przerzutki, hamulce tarczowe, centrowanie kół.\n\nSzybki serwis przed sezonem.\n\nOdbiór po wcześniejszym umówieniu.", 80, 'rower-serwis.jpg', 'service'],
            ['Stolarka naprawcza — meble i okna', "Regulacja okien PCV, naprawa zawiasów, składanie mebli.\n\nDrobne usługi stolarskie na miejscu.\n\nWycena po zdjęciach.", 0, 'stolarka.jpg', 'service'],
            ['Naprawa telefonów — wymiana ekranów', "iPhone i Samsung, oryginalne i OEM wyświetlacze.\n\nCzas realizacji zwykle 24 h.\n\nGwarancja 3 miesiące na montaż.", 199, 'telefon-serwis.jpg', 'service'],
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    private static function goods(
        string $title,
        string $description,
        int $price,
        string $imageName,
        ?string $condition = null,
        bool $negotiable = true,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'image_name' => $imageName,
            'kind' => 'goods',
            'condition' => $condition ?? AdCondition::Used->value,
            'negotiable' => $negotiable,
            'delivery' => ['personal', 'parcel_locker', 'courier'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function service(
        string $title,
        string $description,
        int $price,
        string $imageName,
        bool $negotiable = true,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'price' => $price > 0 ? $price : null,
            'image_name' => $imageName,
            'kind' => 'service',
            'condition' => null,
            'negotiable' => $negotiable,
            'delivery' => [],
        ];
    }
}
