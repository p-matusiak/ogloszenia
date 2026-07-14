<?php

declare(strict_types=1);

namespace Database\Seeders;

/**
 * Szablony ogłoszeń dla kategorii bez dedykowanego katalogu w DemoMarketplaceTemplates.
 *
 * @phpstan-type SpecEntry array{0: string, 1: string, 2: int, 3: string, 4: string}
 */
final class DemoMarketplaceCategorySpecs
{
    /**
     * @return array<string, list<SpecEntry>>
     */
    public static function all(): array
    {
        return [
            'dzialki-i-grunty' => [
                ['Działka budowlana 1200 m² — media w drodze', "Działka w strefie MN, wymiary ok. 30×40 m.\n\nDojazd drogą publiczną, w otoczeniu nowych domów jednorodzinnych.\n\nGeodezja i mapa do wglądu po kontakcie.", 320000, 'dzialka-budowlana.jpg', 'service'],
                ['Działka rekreacyjna nad jeziorem — 2500 m²', "Działka leśno-rekreacyjna z dostępem do wody.\n\nMożliwość postawienia domku letniskowego według MPZP.\n\nSpokojna okolica, dojazd całoroczny.", 185000, 'dzialka-rekreacyjna.jpg', 'service'],
                ['Grunt rolny 3 ha — pod uprawę', "Grunt klasy III, obecnie pod łąką.\n\nDojazd drogą polną, woda z studni głębinowej na sąsiedniej działce.\n\nCena za hektar do omówienia.", 240000, 'grunt-rolny.jpg', 'service'],
                ['Działka inwestycyjna pod hale — 1,2 ha', "Teren przy głównej drodze, PUM w trakcie.\n\nIdealna pod magazyn lub małą produkcję.\n\nWycena indywidualna po spotkaniu.", 890000, 'dzialka-inwestycyjna.jpg', 'service'],
                ['Działka siedliskowa 800 m² — las w sąsiedztwie', "Kształt prostokątny, lekko pochyła.\n\nPrąd w granicy działki, woda ze studni do wykonania.\n\nZapraszam na oględziny w weekend.", 145000, 'dzialka-siedliskowa.jpg', 'service'],
            ],
            'lokale-i-biura' => [
                ['Lokal użytkowy 85 m² — witryna na deptaku', "Powierzchnia na parterze, duże przeszklenie, zaplecze socjalne.\n\nIdealny pod butik, gabinet lub usługi.\n\nCzynsz administracyjny niski.", 620000, 'lokal-handlowy.jpg', 'service'],
                ['Biuro 120 m² — open space, klimatyzacja', "Trzy sale + sala konferencyjna, recepcja.\n\nW pełni umeblowane, gotowe do wejścia.\n\nParking dla 4 aut w cenie.", 890000, 'biuro-open-space.jpg', 'service'],
                ['Magazyn 350 m² — rampa załadunkowa', "Hala z wysokością 6 m, brama segmentowa.\n\nMonitoring, ogrzewanie gazowe.\n\nMożliwy najem krótko- lub długoterminowy.", 1250000, 'magazyn-hala.jpg', 'service'],
                ['Lokal gastronomiczny 60 m² — wentylacja', "Po remoncie, okablowanie pod kuchnię, zaplecze sanitarne.\n\nDuży ruch pieszy w okolicy.\n\nMożliwe przejęcie wyposażenia za dopłatą.", 480000, 'lokal-gastronomiczny.jpg', 'service'],
                ['Biuro w coworkingu — 4 stanowiska', "Wynajem elastyczny, internet światłowodowy, sala spotkań w cenie.\n\nIdealne dla małego zespołu IT lub agencji.\n\nUmowa od 3 miesięcy.", 3200, 'coworking.jpg', 'service'],
            ],
            'garaze-i-parkingi' => [
                ['Miejsce postojowe w hali — centrum miasta', "Garaż podziemny, szerokie miejsce, brama na pilota.\n\nMonitoring 24/7, winda bezpośrednio do klatki.\n\nWolne od zaraz.", 85000, 'miejsce-garaz.jpg', 'service'],
                ['Garaż blaszany 3×6 m — montaż w cenie', "Garaż jednostanowiskowy, fundament pod klienta.\n\nKolor grafit, drzwi dwuskrzydłowe.\n\nRealizacja w ciągu 2 tygodni.", 8900, 'garaz-blaszany.jpg', 'service'],
                ['Boks garażowy 18 m² — osiedle zamknięte', "Suchy, oświetlony, prąd w boksie.\n\nIdealny na motocykl, rowery i sezonowe przechowywanie.\n\nCzynsz 120 zł/mies.", 42000, 'boks-garazowy.jpg', 'service'],
                ['Parking naziemny — dzierżawa długoterminowa', "Miejsce oznakowane, szlaban na pilota.\n\nBlisko centrum handlowego.\n\nUmowa min. 12 miesięcy.", 350, 'parking-naziemny.jpg', 'service'],
                ['Wiata garażowa dwustanowiskowa', "Konstrukcja stalowa, blacha trapezowa, szerokość 6 m.\n\nMontaż na płycie betonowej klienta.\n\nWycena po oględzinach terenu.", 12900, 'wiata-garazowa.jpg', 'service'],
            ],
            'pokoje-i-stancje' => [
                ['Pokój jednoosobowy — studenci, tramwaj 3 min', "Pokój 12 m² w mieszkaniu 3-pokojowym, wspólna kuchnia i łazienka.\n\nInternet w cenie, spokojni współlokatorzy.\n\nKaucja jednomiesięczna.", 1200, 'pokoj-studencki.jpg', 'service'],
                ['Stancja dla pracownika — prywatna łazienka', "Pokój z aneksem kuchennym i własną łazienką.\n\nBlisko strefy przemysłowej, parking przy budynku.\n\nUmowa najmu okazjonalnego.", 1800, 'stancja-pracownicza.jpg', 'service'],
                ['Pokój w centrum — krótkoterminowy wynajem', "W pełni umeblowany, pościel i ręczniki w cenie.\n\nIdealny na delegacje 1–3 miesiące.\n\nSprzątanie co dwa tygodnie.", 2200, 'pokoj-centrum.jpg', 'service'],
                ['Kawalerka do wynajęcia — osobne wejście', "Małe mieszkanie 28 m² z aneksem, ogrzewanie miejskie.\n\nCiche podwórko, tramwaj pod domem.\n\nWolne od 1. następnego miesiąca.", 2400, 'kawalerka-wynajem.jpg', 'service'],
                ['Pokój dla studentki — tylko osoby niepalące', "Mieszkanie dla dwóch osób, pokój 14 m² z balkonem.\n\nPralnia w piwnicy, rowerownia.\n\nPreferuję spokojną osobę.", 1100, 'pokoj-balkon.jpg', 'service'],
            ],
            'peryferia' => [
                ['Mysz Logitech MX Master 3S — grafit', "Bezprzewodowa mysz biurowa, mało używana rok.\n\nScroll magnetyczny, multi-device, stacja ładowania w zestawie.\n\nDziała bez zarzutu.", 249, 'mysz-mx-master.jpg', 'goods'],
                ['Klawiatura Keychron K2 — przełączniki Brown', "Mechaniczna, podświetlenie RGB, układ 75%.\n\nUżywana do pracy zdalnej, bez problemów z klawiszami.\n\nOryginalne pudełko.", 299, 'klawiatura-keychron.jpg', 'goods'],
                ['Monitor Dell 27\" 4K USB-C', "Panel IPS, 60 Hz, hub USB w monitorze.\n\nBez martwych pikseli, lekko używany w biurze domowym.\n\nStojak regulowany wysokością.", 899, 'monitor-dell-27.jpg', 'goods'],
                ['Drukarka laserowa Brother HL-L2350DW', "Mono, duplex, Wi-Fi.\n\nToner po wymianie, wydrukowała ok. 800 stron.\n\nIdealna do domowego biura.", 349, 'drukarka-brother.jpg', 'goods'],
                ['Kamera internetowa Logitech C920', "1080p, mikrofon stereo, nakładka prywatności.\n\nUżywana na spotkania online.\n\nWysyłka InPost.", 179, 'kamera-c920.jpg', 'goods'],
            ],
            'rtv-i-audio' => [
                ['Słuchawki Sony WH-1000XM5 — czarne', "Flagowe ANC, etui i kabel w zestawie.\n\nBateria trzyma jak nowe, bez wytarć na pałąku.\n\nParowanie z dwoma urządzeniami.", 799, 'sony-wh1000xm5.jpg', 'goods'],
                ['Soundbar Samsung HW-Q700C', "Dolby Atmos, subwoofer bezprzewodowy.\n\nKupiony rok temu, komplet kabli HDMI i optyczny.\n\nBrak uszkodzeń.", 1199, 'soundbar-samsung.jpg', 'goods'],
                ['Głośnik JBL Charge 5 — niebieski', "Wodoodporny, idealny na taras i rower.\n\nBateria sprawna, bez przesterowań.\n\nOryginalne opakowanie.", 399, 'jbl-charge5.jpg', 'goods'],
                ['Wzmacniacz stereo Yamaha A-S301', "Klasyczny amplituner, dwa zestawy głośnikowe.\n\nSprawny, bez trzasków przy regulacji.\n\nOdbiór osobisty — ciężki.", 899, 'wzmacniacz-yamaha.jpg', 'goods'],
                ['Telewizor LG 55\" OLED C2', "Panel OLED, 120 Hz, webOS.\n\nMało używany, bez wypaleń, pilot i stojak w zestawie.\n\nMożliwy transport po uzgodnieniu.", 2499, 'tv-lg-oled.jpg', 'goods'],
            ],
            'aparaty-fotograficzne' => [
                ['Canon EOS R6 Mark II — body', "Bezlusterkowiec full frame, przebieg migawki ok. 18 tys.\n\nBez kurzu na matrycy, dwa oryginalne akumulatory.\n\nSprzedaję po przejściu na mniejszy system.", 7499, 'canon-r6.jpg', 'goods'],
                ['Obiektyw Sony 24-70 mm f/2.8 GM II', "Uniwersalny zoom reportażowy, stan idealny.\n\nFiltr UV w zestawie, korpus bez rys.\n\nWysyłka ubezpieczona.", 5999, 'sony-24-70.jpg', 'goods'],
                ['Fujifilm X-T5 — srebrny, 18-55 kit', "Aparat z obiektywem kit, 40 MP, film simulation.\n\nMało używany, komplet pudełek i pasek.\n\nIdealny na podróże.", 4899, 'fujifilm-xt5.jpg', 'goods'],
                ['GoPro Hero 12 Black', "Kamera sportowa, 3 baterie, uchwyt na kask.\n\nNagrania stabilne, obudowa bez pęknięć.\n\nKarta pamięci 128 GB w zestawie.", 1299, 'gopro-hero12.jpg', 'goods'],
                ['Statyw Manfrotto z głowicą kulową', "Aluminiowy, udźwig 8 kg, szybkie blokady.\n\nLekkie rysy na nogach od transportu.\n\nSprawdzony w studio i w terenie.", 449, 'statyw-manfrotto.jpg', 'goods'],
            ],
            'konsole-i-gry' => [
                ['PlayStation 5 — edycja z napędem', "Konsola z dwoma padami DualSense, kable w zestawie.\n\nMało używana, cicha, bez przegrzewania.\n\nKilka gier cyfrowych na koncie — do uzgodnienia.", 1899, 'ps5.jpg', 'goods'],
                ['Xbox Series X 1 TB', "Konsola + pad, Game Pass wygasł.\n\nStan bardzo dobry, stojak pionowy w zestawie.\n\nOdbiór lub wysyłka.", 1699, 'xbox-series-x.jpg', 'goods'],
                ['Nintendo Switch OLED — biała', "Konsola z dockiem, Joy-Con bez dryfu.\n\nEkran bez rys, futerał i szkło w zestawie.\n\nIdealna w podróż.", 1299, 'switch-oled.jpg', 'goods'],
                ['Gra Elden Ring PS5 — pudełkowa', "Oryginalna płyta, booklet, stan idealny.\n\nUkończona jednokrotnie.\n\nWysyłka InPost.", 129, 'elden-ring.jpg', 'goods'],
                ['Kontroler Pro 8BitDo — PC i Switch', "Pad bezprzewodowy, programowalne przyciski.\n\nUżywany do gier indie na PC.\n\nŁadowanie USB-C.", 149, 'pad-8bitdo.jpg', 'goods'],
            ],
            'akcesoria-elektroniczne' => [
                ['Etui Spigen do iPhone 15 Pro — przezroczyste', "Nowe, nieużywane, oryginalne opakowanie.\n\nOchrona MagSafe, wzmocnione narożniki.\n\nWysyłka od ręki.", 49, 'etui-iphone.jpg', 'goods'],
                ['Powerbank Anker 20000 mAh — PD 30 W', "Szybkie ładowanie telefonu i laptopa USB-C.\n\nPojemność sprawna, kabel w zestawie.\n\nMało używany w podróżach.", 129, 'powerbank-anker.jpg', 'goods'],
                ['Ładowarka samochodowa 2×USB-C 65 W', "Gałęzie zapalniczki, obsługa laptopa w aucie.\n\nUżywana kilka miesięcy.\n\nPasuje do większości aut.", 89, 'ladowarka-samochodowa.jpg', 'goods'],
                ['Szkło hartowane Samsung S24 — 2 szt.', "Nieużywane folie w zestawie z aplikatorem.\n\nPełne pokrycie ekranu, bez bąbli.\n\nCena za obie sztuki.", 29, 'szklo-samsung.jpg', 'goods'],
                ['Stacja dokująca USB-C — 7 portów', "Hub z HDMI 4K, LAN, czytnik SD.\n\nUżywany do biurka z jednym kablem do laptopa.\n\nSprawny bez wyjątków.", 199, 'hub-usb-c.jpg', 'goods'],
            ],
            'ogrod' => [
                ['Kosiarka spalinowa Honda HRG466 — napęd', "Kosiarka z napędem na koła, kosz i mulczowanie.\n\nSerwis w tym sezonie, świeży olej.\n\nIdealna na trawnik do 800 m².", 899, 'kosiarka-honda.jpg', 'goods'],
                ['Stół ogrodowy ratanowy 160 cm + 6 krzeseł', "Stół i krzesła na taras, poduszki w cenie.\n\nPrzechowywane zimą w altanie.\n\nLekkie przebarwienia od słońca.", 1299, 'meble-ogrodowe.jpg', 'goods'],
                ['Grill Weber Spirit E-310', "Gazowy, trzy palniki, ruszt żeliwny.\n\nUżywany sezonowo, czyszczony po każdym grillowaniu.\n\nButla nie w zestawie.", 1499, 'grill-weber.jpg', 'goods'],
                ['Pompa do basenu Intex 8 m³/h', "Filtr piaskowy, węże i łączniki w komplecie.\n\nSezon letni za sobą, sprawna.\n\nMożliwa wysyłka.", 349, 'pompa-basen.jpg', 'goods'],
                ['Szklarnia aluminiowa 3×2 m', "Konstrukcja z poliwęglanu, drzwi obie strony.\n\nStabilna, bez pęknięć.\n\nDemontaż i odbiór własny.", 799, 'szklarnia.jpg', 'goods'],
            ],
            'lampy-sufitowe' => [
                ['Lampa sufitowa LED Philips Hue — biała', "Sterowanie aplikacją, barwa i jasność regulowane.\n\nMontaż na suficie podwieszanym.\n\nMostek Hue w zestawie.", 449, 'lampa-sufit-hue.jpg', 'goods'],
                ['Plafoniera kryształowa 60 cm — salon', "Elegancki plafon z kryształkami, żarówki LED w cenie.\n\nUżywana w salonie, bez uszkodzeń.\n\nOdbiór — delikatne pakowanie.", 399, 'plafoniera-krysztal.jpg', 'goods'],
                ['Lampa sufitowa industrialna — loft', "Metalowa tarcza, czarna, E27.\n\nPasuje do kuchni i jadalni w stylu loft.\n\nNowa, nieużywana.", 189, 'lampa-loft.jpg', 'goods'],
                ['Żyrandol 5-punktowy — mosiądz', "Klasyczny żyrandol, klosze szklane matowe.\n\nPo remoncie salonu — sprzedaję nadmiar.\n\nWszystkie punkty świecą.", 599, 'zyrandol-mosiadz.jpg', 'goods'],
                ['Panel LED 60×60 — biuro', "Panel wpuszczany, neutralna biel 4000 K.\n\nSprawny driver, mało używany w biurze.\n\nMożliwa wysyłka.", 79, 'panel-led-60.jpg', 'goods'],
            ],
            'lampki-biurkowe' => [
                ['Lampka biurkowa BenQ ScreenBar Halo', "Oświetlenie monitora bez olśnienia, sterowanie bezprzewodowe.\n\nIdealna do pracy nocnej.\n\nStan bardzo dobry.", 499, 'benq-screenbar.jpg', 'goods'],
                ['Lampka IKEA FORSÅ — regulowana', "Klasyczna lampka biurkowa, klosz metalowy.\n\nŻarówka LED w cenie.\n\nDrobne ślady użytkowania.", 49, 'lampka-forsa.jpg', 'goods'],
                ['Lampka z ładowarką Qi — drewno', "Podstawa bambusowa, port USB-C, dotykowy dimmer.\n\nŁaduje telefon i oświetla biurko.\n\nNowa w opakowaniu.", 129, 'lampka-qi.jpg', 'goods'],
                ['Lampka architektoniczna Anglepoise — mini', "Ikoniczna konstrukcja, regulacja w wielu płaszczyznach.\n\nKolor sage green.\n\nSprzedaję po zmianie wystroju.", 249, 'anglepoise.jpg', 'goods'],
                ['Lampka nocna dla dziecka — projektor', "Projekcja gwiazd na sufit, timer wyłączenia.\n\nUżywana rok, bez uszkodzeń.\n\nIdealna do pokoju 3–8 lat.", 59, 'lampka-projektor.jpg', 'goods'],
            ],
            'zrodla-swiatla' => [
                ['Żarówka LED Philips Hue White E27', "Sterowanie aplikacją, temperatura barwowa regulowana.\n\nUżywana w salonie, działa bez zarzutu.\n\nSprzedaję cztery sztuki tego modelu.", 149, 'zarowki-hue.jpg', 'goods'],
                ['Taśma LED RGB 5 m — sterownik Wi-Fi', "Taśma z klejem 3M, pilot i aplikacja.\n\nMontowana za TV, demontaż bez uszkodzeń.\n\nZasilacz w zestawie.", 69, 'tasma-led-rgb.jpg', 'goods'],
                ['Żarówka GU10 LED Osram 5 W — neutralna biel', "400 lm, 4000 K, trzonek GU10.\n\nPo wymianie oświetlenia w kuchni — nadmiar.\n\nNowe w opakowaniu, 10 sztuk.", 39, 'zarowki-gu10.jpg', 'goods'],
                ['Panel LED slim 120 cm — warsztat', "Listwa LED 36 W, zasilacz i przełącznik.\n\nJasne świato robocze, montaż pod szafką.\n\nSprawny.", 89, 'listwa-led.jpg', 'goods'],
                ['Świetlówka LED T8 150 cm — chłodna', "Zamiennik tradycyjnej świetlówki, starter niepotrzebny.\n\n5 sztuk w opakowaniu.\n\nNieużywane.", 45, 'swietlowka-t8.jpg', 'goods'],
            ],
            'narzedzia' => [
                ['Wiertarko-wkrętarka Makita DHP482 — 2 akumulatory 18 V', "Ładowarka i walizka Makita, moment obrotowy regulowany.\n\nUżywana w domu, bez spadku mocy.\n\nBity w zestawie.", 449, 'makita-wiertarka.jpg', 'goods'],
                ['Szlifierka kątowa Bosch GWS 750', "125 mm, tarcza w zestawie.\n\nSprawna, bez luzów na wirze.\n\nDo cięcia i szlifowania metalu.", 199, 'szlifierka-bosch.jpg', 'goods'],
                ['Klucze nasadowe Hogert Technik — 94 elementy', "Chromowane nasadki 1/4 i 1/2, grzechotki i przedłużki.\n\nWalizka kompletna, mało używane.\n\nIdealne do garażu.", 299, 'klucze-nasadowe.jpg', 'goods'],
                ['Pilarka tarczowa DeWalt 1400 W', "Cięcie drewna i płyt, prowadnica w zestawie.\n\nOstrze ostre, osłona sprawna.\n\nOdbiór osobisty.", 349, 'pilarka-dewalt.jpg', 'goods'],
                ['Kompresor 50 L — 2 KM', "Sprężarka do malowania i pneumatyki.\n\nRegularnie odolejany, manometr sprawny.\n\nWąż nie w zestawie.", 599, 'kompresor-50l.jpg', 'goods'],
            ],
            'dekoracje' => [
                ['Wazon ceramiczny 45 cm — boho', "Ręcznie malowany wazon, idealny na suszone trawy.\n\nBez pęknięć, stabilna podstawa.\n\nOdbiór ostrożny.", 89, 'wazon-boho.jpg', 'goods'],
                ['Obraz olejny — pejzaż górski', "Oryginalny obraz na płótnie 60×80 cm,rama drewniana.\n\nPasuje do salonu w stylu klasycznym.\n\nWysyłka po uzgodnieniu pakowania.", 449, 'obraz-pejzaz.jpg', 'goods'],
                ['Lustro okrągłe 80 cm — złota rama', "Lustro wiszące, mocowanie w zestawie.\n\nLekkie rysy na ramie widoczne z bliska.\n\nOdbiór własny.", 199, 'lustro-okragle.jpg', 'goods'],
                ['Poduszka dekoracyjna Ikea SANELA — beż 50×50 cm', "Poszewka lniana w kolorze beżowym, wypełnienie piórkowe.\n\nPrana, bez plam.\n\nSprzedaję cztery sztuki tego samego modelu.", 129, 'poduszki-dekor.jpg', 'goods'],
                ['Świecznik mosiężny West Elm — stożek glamour', "Stojak na świeczkę stożkową, wykończenie mosiądz.\n\nPo zmianie wystroju jadalni.\n\nPolerowany, bez korozji — sprzedaję trzy sztuki.", 79, 'swieczniki-mosiadz.jpg', 'goods'],
            ],
            'odziez-meska' => [
                ['Kurtka parka The North Face — L', "Męska, czarna, membrana DryVent.\n\nNoszona jeden sezon zimowy, bez przetarć.\n\nKaptur odczepiany.", 499, 'kurtka-meska-tnf.jpg', 'goods'],
                ['Marynarka wełniana — rozmiar 50', "Granatowa, slim fit, podszyta poliestrem.\n\nDo pracy i na uroczystości.\n\nCzyszczona chemicznie.", 249, 'marynarka-welna.jpg', 'goods'],
                ['Bluza Ralph Lauren — M', "Klasyczna bluza z logo, szary melanż.\n\nStan bardzo dobry, bez kulek.\n\nOryginalna metka.", 199, 'bluza-ralph.jpg', 'goods'],
                ['Koszula męska Calvin Klein — biała 41/42', "Slim fit, bawełna, kołnierz klasyczny.\n\nNoszona w biurze, prana w pralni.\n\nMam też wersje błękitne — zapytaj w wiadomości.", 149, 'koszule-biznes.jpg', 'goods'],
                ['Spodnie chinosy Dockers — W34 L32', "Beżowe, bawełna, lekko dopasowane.\n\nMało używane.\n\nWysyłka możliwa.", 89, 'chinosy-dockers.jpg', 'goods'],
            ],
            'obuwie' => [
                ['Buty trekkingowe Salomon X Ultra 4 — 42', "Wodoodporne, bieżnik jak nowy.\n\nJedna trasa w Tatrach, regularnie impregnowane.\n\nIdealne na szlaki.", 349, 'buty-salomon.jpg', 'goods'],
                ['Sneakersy Nike Air Max 90 — 43', "Klasyczne biało-szare, stan dobry.\n\nNoszone na co dzień, bez rozdarć.\n\nOryginalne pudełko.", 279, 'nike-air-max.jpg', 'goods'],
                ['Buty wizytowe Oxford — skóra 44', "Czarne, pełne skóry, podeszwa w dobrym stanie.\n\nDo garnituru i na wesele.\n\nWkładki wymieniane.", 199, 'oxford-skora.jpg', 'goods'],
                ['Kozaki zimowe Timberland — 41', "Ocieplane, wodoodporne, żółte szwy.\n\nUżywane dwa sezony, regularnie czyszczone.\n\nWysyłka InPost.", 329, 'timberland.jpg', 'goods'],
                ['Sandały Teva — 40', "Letnie, regulowane paski, podeszwa Vibram.\n\nPo sezonie wakacyjnym.\n\nMycie w pralce niepotrzebne.", 129, 'sandały-teva.jpg', 'goods'],
            ],
            'bizuteria' => [
                ['Pierścionek zaręczynowy — brylant 0.4 ct', "Złoto 585, certyfikat kamienia.\n\nRozmiar 54, możliwa zmiana rozmiaru u jubilera.\n\nSprzedaję z szacunku do prywatności.", 4999, 'pierscionek-brylant.jpg', 'goods'],
                ['Zegarek damski Citizen Eco-Drive', "Zasilanie solarne, bransoleta stalowa.\n\nBez zarysowań na szkle, działa precyzyjnie.\n\nPudełko i dokumenty.", 699, 'zegarek-citizen.jpg', 'goods'],
                ['Naszyjnik srebrny z cyrkoniami — 45 cm', "Srebro 925, zapięcie magnetyczne.\n\nNoszony okazjonalnie.\n\nWoreczek jubilerski w zestawie.", 149, 'naszyjnik-srebro.jpg', 'goods'],
                ['Bransoletka pandora — 12 charmsów', "Srebrna baza i charmsy mix, temat podróże.\n\nWszystkie charmsy widoczne na zdjęciu, bez wad.\n\nMożliwa wysyłka ubezpieczona.", 899, 'bransoletka-pandora.jpg', 'goods'],
                ['Kolczyki złote koła — 18 mm', "Złoto 333, lekkie, zapięcie snap.\n\nNowe, nieużywane, prezent nie trafił.\n\nOryginalne pudełko.", 199, 'kolczyki-zlote.jpg', 'goods'],
            ],
            'akcesoria-modowe' => [
                ['Torebka skórzana Michael Kors — brąz', "Skóra naturalna, komora na laptop 13\".\n\nStan bardzo dobry, bez przetarć na rogach.\n\nDust bag w zestawie.", 449, 'torebka-mk.jpg', 'goods'],
                ['Pasek skórzany Hermès style — 90 cm', "Skóra cielęca, klamra złota.\n\nNie oryginał Hermès — wysokiej jakości replika.\n\nInformuję uczciwie w opisie.", 89, 'pasek-skora.jpg', 'goods'],
                ['Okulary przeciwsłoneczne Ray-Ban Aviator', "Model klasyczny, szkła bez rys.\n\nEtui i ściereczka w zestawie.\n\nOryginał z salonu optycznego.", 349, 'rayban-aviator.jpg', 'goods'],
                ['Szal kaszmirowy — beż', "Miękki, duży 200×70 cm.\n\nNoszony delikatnie, bez pulli.\n\nIdealny na jesień.", 129, 'szal-kaszmir.jpg', 'goods'],
                ['Czapka zimowa Canada Goose — czarna', "Ocieplana, logo naszywka, rozmiar uniwersalny.\n\nJeden sezon użytkowania.\n\nCena do negocjacji.", 199, 'czapka-zimowa.jpg', 'goods'],
            ],
            'wozki-dzieciece' => [
                ['Wózek Bugaboo Fox 5 — szary melange', "Wózek spacerowy z gondolą, parasolka i moskitiera.\n\nPo jednym dziecku, zadbany, koła bez zużycia.\n\nInstrukcja i pudełka częściowo.", 2499, 'wozek-bugaboo.jpg', 'goods'],
                ['Wózek spacerowy Baby Jogger City Mini — GT2', "Składany jedną ręką, duże koła, hamulec ręczny.\n\nLekki, idealny do miasta.\n\nBarierka i pałąk w zestawie.", 899, 'wozek-city-mini.jpg', 'goods'],
                ['Gondola do wózka Cybex Priam — deep black', "Gondola oddzielnie, adaptery pod ramę Priam.\n\nCzysta, bez plam, materiał jak nowy.\n\nMożliwa wysyłka po uzgodnieniu.", 699, 'gondola-cybex.jpg', 'goods'],
                ['Wózek wielofunkcyjny 3w1 — wyczynowy', "Gondola, spacerówka i fotelik auto w zestawie.\n\nUżywany rok, wszystkie elementy sprawne.\n\nOdbiór osobisty.", 1299, 'wozek-3w1.jpg', 'goods'],
                ['Wózek parasolka Chicco Lite Way — czerwony', "Lekki, do bagażnika, daszek UV.\n\nIdealny jako drugi wózek na wakacje.\n\nKosz bez uszkodzeń.", 199, 'wozek-parasolka.jpg', 'goods'],
            ],
            'foteliki-samochodowe' => [
                ['Fotelik Cybex Sirona S2 i-Size — 0–4 lata', "Obrotowy 360°, boczna ochrona, ISOFIX.\n\nPo jednym dziecku, bez wypadku.\n\nWkładka dla niemowlęcia w zestawie.", 899, 'fotelik-cybex-sirona.jpg', 'goods'],
                ['Fotelik Maxi-Cosi Pebble 360 — baza FamilyFix', "Nosidełko z bazą ISOFIX, grupa 0+.\n\nData produkcji 2022, nie wypadkowy.\n\nPoszycie czyste.", 649, 'fotelik-maxi-cosi.jpg', 'goods'],
                ['Fotelik podwyższający Joie Trillo — 15–36 kg', "Z oparciem, pasuje do większości aut.\n\nLekki, łatwy do przenoszenia między autami.\n\nStan bardzo dobry.", 149, 'fotelik-podwyzszajacy.jpg', 'goods'],
                ['Fotelik Avionaut Pixel — 40–86 cm', "Mały fotelik dla niemowląt, adapter do wózka.\n\nUżywany 8 miesięcy.\n\nInstrukcja i pudełko.", 399, 'fotelik-avionaut.jpg', 'goods'],
                ['Fotelik Kinderkraft XPAND 2 i-Size', "Rośnie z dzieckiem 100–150 cm.\n\nISOFIX i Top Tether, regulacja zagłówka.\n\nSprzedaję po przejściu na większy.", 499, 'fotelik-kinderkraft.jpg', 'goods'],
            ],
            'ubranka-dzieciece' => [
                ['Kombinezon zimowy Reima 104 — granat', "Ocieplany, wodoodporny, rękawice w komplecie.\n\nJeden sezon, bez plam i dziur.\n\nIdealny na przedszkole.", 149, 'kombinezon-reima.jpg', 'goods'],
                ['Body niemowlęce Lupilu — 62 cm, bawełna organiczna', "Body z zatrzaskami, kolor ecru.\n\nNowe z metkami, prezent nie wykorzystany.\n\nSprzedaję pięć sztuk jednego rozmiaru.", 59, 'body-zestaw.jpg', 'goods'],
                ['Sukienka świąteczna 110 — czerwona', "Jednorazowo założona na jasełka.\n\nTiul i aksamit, kokarda z tyłu.\n\nBez uszkodzeń.", 49, 'sukienka-swiateczna.jpg', 'goods'],
                ['Bluzy dresowe 3 szt. — rozmiar 128', "Cotton, marki Coccodrillo i Reserved.\n\nPo jednym dziecku, prane delikatnie.\n\nTrzy bluzy w kolorach widocznych na zdjęciu.", 79, 'bluzy-dziecko.jpg', 'goods'],
                ['Kurtka przejściowa 116 — żółta', "Wiatroszczelna, kaptur, odblaski.\n\nLekkie ślady zabawy, bez dziur.\n\nWysyłka InPost.", 69, 'kurtka-dziecko.jpg', 'goods'],
            ],
            'akcesoria-dla-dzieci' => [
                ['Nosidełko ergonomiczne Ergobaby Omni 360', "Noszenie przodem, bokiem i na plecach.\n\nWkładka dla noworodka, instrukcja w zestawie.\n\nPrane, bez plam.", 249, 'nosidelko-ergobaby.jpg', 'goods'],
                ['Monitor oddechu Angelcare AC517', "Czujnik pod materacem, jednostka rodzicielska.\n\nSprawny, baterie wymieniane.\n\nUżywany 6 miesięcy.", 199, 'monitor-oddechu.jpg', 'goods'],
                ['Termos na mleko Tommee Tippee + podgrzewacz samochodowy', "Termos na mleko i grzejnik na zapalniczkę 12 V.\n\nTermos trzyma temperaturę 6 h.\n\nCzysty, sterylizowany.", 89, 'termos-mleko.jpg', 'goods'],
                ['Kojec turystyczny 120×120 — składany', "Lekki, torba transportowa, materac cienki w cenie.\n\nIdealny na wyjazdy i wizyty u rodziny.\n\nBez uszkodzeń stelaża.", 129, 'kojec-turystyczny.jpg', 'goods'],
                ['Podgrzewacz do butelek Philips Avent', "Szybkie podgrzewanie wody i sterylizacja.\n\nUżywany rok, bez kamienia wewnątrz.\n\nInstrukcja PL.", 79, 'podgrzewacz-avent.jpg', 'goods'],
            ],
            'silownia-i-fitness' => [
                ['Hantle regulowane HMS 2×20 kg — gryf 35 cm', "Gryfy 35 cm, obciążenia dyskowe.\n\nDo treningu w domu, wszystkie tarcze w komplecie.\n\nOdbiór własny — ciężkie.", 499, 'hantle-regulowane.jpg', 'goods'],
                ['Bieżnia elektryczna Domyos T520B', "Składana, nachylenie manualne, maks. 13 km/h.\n\nUżywana w mieszkaniu, smarowana.\n\nPas bez przetarć.", 899, 'bieznia-domyos.jpg', 'goods'],
                ['Rowerek treningowy magnetyczny', "Opór regulowany, puls na uchwytach.\n\nCichy, idealny do cardio w domu.\n\nStan bardzo dobry.", 449, 'rowerek-magnetyczny.jpg', 'goods'],
                ['Mata do jogi Manduka Pro — 5 mm', "Antypoślizgowa, grubość 5 mm, kolor midnight.\n\nUżywana na zajęciach, czyszczona.\n\nTorba w zestawie.", 199, 'mata-manduka.jpg', 'goods'],
                ['Ławka treningowa składana — regulacja', "Ławka pod sztangę, oparcie regulowane.\n\nStabilna, maks. 250 kg użytkownika.\n\nOdbiór po umówieniu.", 349, 'lawka-treningowa.jpg', 'goods'],
            ],
            'turystyka' => [
                ['Namiot 4-osobowy Coleman — szybki rozstaw', "Sypialnia i przedsionek, wodoodporność 3000 mm.\n\nUżywany dwa luty, bez dziur w materiale.\n\nSłupki i śledzie kompletne.", 399, 'namiot-coleman.jpg', 'goods'],
                ['Śpiwór puchowy -5°C — kompaktowy', "Puch kaczy, kompresyjny worek.\n\nPo kilku biwakach w Tatrach.\n\nBez przetarć, zamek sprawny.", 299, 'spiwor-puch.jpg', 'goods'],
                ['Plecak trekkingowy Osprey 65 L', "Regulowany system noszenia, pokrowiec przeciwdeszczowy.\n\nIdealny na kilkudniowe wyprawy.\n\nLekkie ślady użytkowania.", 449, 'plecak-osprey.jpg', 'goods'],
                ['Kuchenka turystyczna MSR PocketRocket 2 + garnki tytanowe', "Palnik na kartusze i dwa garnki tytanowe na 2 osoby.\n\nSprawna, bez zatorów.\n\nKartusz pusty — bez gazu.", 199, 'kuchenka-msr.jpg', 'goods'],
                ['Latarka czołowa Petzl — 450 lm', "USB ładowanie, tryb czerwony, wodoszczelna.\n\nUżywana na szlaku, bateria trzyma długo.\n\nPasek bez zużycia.", 129, 'latarka-petzl.jpg', 'goods'],
            ],
            'muzyka-i-instrumenty' => [
                ['Gitara akustowa Yamaha F310', "Klasyczna gitara dla początkujących.\n\nŚwieże struny, futerał miękki w zestawie.\n\nBrzmi równo, bez pęknięć drewna.", 349, 'gitara-yamaha.jpg', 'goods'],
                ['Keyboard Yamaha P-125 — 88 klawiszy', "Waga klawiszy graded, pedał sustain.\n\nUżywany w domu, bez dead keys.\n\nStojak opcjonalnie.", 1299, 'keyboard-yamaha.jpg', 'goods'],
                ['Saksofon altowy — student', "Instrument po jednym właścicielu, nowe ustnik.\n\nIdealny na szkołę muzyczną.\n\nFuterał twardy w cenie.", 1499, 'saksofon-alt.jpg', 'goods'],
                ['Perkusja elektroniczna Roland TD-07DMK', "Moduł z padami mesh, pałki i słuchawki.\n\nCicha nauka w mieszkaniu.\n\nKomplet sprawny.", 1899, 'perkusja-roland.jpg', 'goods'],
                ['Wzmacniacz gitarowy Fender Mustang LT25', "Modelowanie brzmień, wbudowany tuner.\n\nDo ćwiczeń w domu, głośnik bez szumów.\n\nKabel jack w zestawie.", 449, 'wzmacniacz-fender.jpg', 'goods'],
            ],
            'kolekcje' => [
                ['Moneta kolekcjonerska PRL 10 zł 1981 — stempel lustrzany', "Moneta okolicznościowa z albumem Fischer.\n\nSprzedaję razem z 19 innymi monetami PRL z lat 60–80.\n\nZdjęcia poszczególnych egzemplarzy na życzenie.", 199, 'monety-prl.jpg', 'goods'],
                ['Znaczki polskie — album 1960–1990', "Kompletny album z opisami, bez luzów.\n\nStan znaczków bardzo dobry.\n\nWysyłka ubezpieczona.", 149, 'znaczki-album.jpg', 'goods'],
                ['Model kolekcjonerski VW Bulli 1:18', "Metalowy model, otwierane drzwi, limitowana seria.\n\nStojak i pudełko kolekcjonerskie.\n\nNigdy nie zdejmowany z pudełka.", 249, 'model-vw-bulli.jpg', 'goods'],
                ['Karta Pokémon Base Set Venusaur — holo 1999', "Karta holo z pierwszej edycji, lekko przytarta krawędź.\n\nW albumie z kilkoma innymi kartami vintage.\n\nUczciwie opisane — bez ukrytych reprintów.", 89, 'karty-pokemon.jpg', 'goods'],
                ['Figurka Funko Pop Iron Man #529 — Marvel', "Figurka z serii Marvel, pudełko lekko przytarte.\n\nStoi stabilnie, bez uszkodzeń.\n\nMam też inne postacie z kolekcji — zapytaj.", 179, 'funko-pop.jpg', 'goods'],
            ],
            'freelance' => [
                ['Copywriting i treści SEO — blog i sklep', "Tworzę artykuły, opisy produktów i teksty landing page.\n\nDoświadczenie w e-commerce i marketplace.\n\nWycena za 1000 zzs lub pakiet miesięczny.", 0, 'copywriting.jpg', 'service'],
                ['Projektowanie logo i identyfikacji wizualnej', "3 propozycje logo, kolory, fonty, pliki wektorowe.\n\nPortfolio wyślę po kontakcie.\n\nTermin zwykle 7–10 dni.", 0, 'logo-design.jpg', 'service'],
                ['Księgowość online dla JDG', "Prowadzenie KPiR, JPK, ZUS, kontakt mailowy.\n\nSpecjalizacja w branży IT i usługach.\n\nPierwsza konsultacja gratis.", 0, 'ksiegowosc-jdg.jpg', 'service'],
                ['Montaż wideo — YouTube i social media', "Cięcie, napisy, prosta animacja, korekcja koloru.\n\nPracuję w Premiere i DaVinci.\n\nCennik od 80 zł/min gotowego materiału.", 0, 'montaz-wideo.jpg', 'service'],
                ['Tłumaczenia EN↔PL — techniczne i marketing', "Native speaker PL, C1 EN.\n\nUmowy, strony www, dokumentacja produktowa.\n\nStawka ustalana za słowo lub projekt.", 0, 'tlumaczenia.jpg', 'service'],
            ],
            'praktyki-i-staze' => [
                ['Staż w marketingu digital — 3 miesiące', "Agencja w Warszawie, praca z Meta Ads i Google Ads.\n\nMentor, realne kampanie, umowa zlecenie.\n\nStart od października.", 0, 'staz-marketing.jpg', 'service'],
                ['Praktyki HR — rekrutacja IT', "Firma software house, obsługa procesów rekrutacyjnych.\n\nHybrydowo 3 dni w biurze.\n\nCV przez wiadomości.", 0, 'praktyki-hr.jpg', 'service'],
                ['Staż graficzny — druk i social media', "Studio graficzne, Adobe CC, przygotowanie do druku.\n\nPortfolio wymagane.\n\nMożliwość zatrudnienia po stażu.", 0, 'staz-grafik.jpg', 'service'],
                ['Praktyki w logistyce — magazyn e-commerce', "Kompletacja zamówień, system WMS, inwentaryzacja.\n\nUmowa o praktyki, dieta.\n\nDyspozycyjność min. 4 dni w tygodniu.", 0, 'praktyki-logistyka.jpg', 'service'],
                ['Staż developerski — Laravel + Vue', "Zespół produktowy, code review, testy.\n\nWymagana znajomość PHP i podstaw frontendu.\n\nForma zdalna z cotygodniowym stand-upem.", 0, 'staz-developer.jpg', 'service'],
            ],
            'sprzatanie' => [
                ['Sprzątanie mieszkań — regularnie lub jednorazowo', "Mycie podłóg, kurz, łazienka, kuchnia.\n\nWłasne środki ekologiczne.\n\nWycena po metrażu i częstotliwości.", 0, 'sprzatanie-mieszkan.jpg', 'service'],
                ['Sprzątanie po remoncie — pył i gruz', "Odkurzacz przemysłowy, mycie okien, doczyszczenie fug.\n\nEkipa 2 osoby, dojazd w cenie w mieście.\n\nFaktura VAT.", 0, 'sprzatanie-po-remoncie.jpg', 'service'],
                ['Mycie okien — dom i biuro', "Ramki, parapety, rolety żaluzjowe.\n\nBezpieczny dostęp bez rusztowania do 3 piętra.\n\nCennik od powierzchni szkła.", 0, 'mycie-okien.jpg', 'service'],
                ['Pranie tapicerki i dywanów — ekstrakcja', "Odkurzacz Karcher, środki antyalergiczne.\n\nSchniecie 4–8 h w zależności od tkaniny.\n\nWycena po zdjęciach.", 0, 'pranie-tapicerki.jpg', 'service'],
                ['Sprzątanie biur — nocna zmiana', "Codziennie po 18:00, opróżnianie koszy, kuchnia, toalety.\n\nUmowa miesięczna, referencje z 3 firm.\n\nProszę o metraż biura.", 0, 'sprzatanie-biur.jpg', 'service'],
            ],
            'nauka-i-korepetycje' => [
                ['Korepetycje matematyka — liceum i matura', "15 lat doświadczenia, przygotowanie do matury podstawowej i rozszerzonej.\n\nZajęcia stacjonarnie lub online.\n\nPierwsza lekcja próbna 50% ceny.", 120, 'korepetycje-matematyka.jpg', 'service'],
                ['Angielski konwersacje — B2/C1', "Native-like fluency, biznes i codzienny angielski.\n\nMałe grupy lub indywidualnie.\n\nMateriały w cenie lekcji.", 100, 'angielski-konwersacje.jpg', 'service'],
                ['Programowanie dla dzieci — Scratch i Python', "Zajęcia 8–14 lat, projekty gier i animacji.\n\nGrupy max 6 osób.\n\nPierwszy miesiąc bez zobowiązań.", 80, 'programowanie-dzieci.jpg', 'service'],
                ['Nauka gry na gitarze — od podstaw', "Akustyczna i elektryczna, teoria na żywo.\n\nDojazd do ucznia w promieniu 10 km.\n\nPierwsza lekcja gratis.", 90, 'nauka-gitary.jpg', 'service'],
                ['Przygotowanie do egzaminu ósmoklasisty — polski', "Pisanie, interpretacja tekstów, testy próbne.\n\nIndywidualny plan nauki.\n\nWyniki uczniów powyżej 80% — chętnie podam referencje.", 110, 'egzamin-osmoklasisty.jpg', 'service'],
            ],
            'pozostale-uslugi' => [
                ['Wizażystka — makijaż ślubny i okolicznościowy', "Próbny makijaż, dojazd do klientki.\n\nKosmetyki profesjonalne, utrwalenie.\n\nRezerwacja terminu z wyprzedzeniem.", 0, 'wizaz-slubny.jpg', 'service'],
                ['Fotograf na event — reportaż i portrety', "Urodziny, komunie, małe wesela.\n\nGaleria online w ciągu 7 dni, retusz podstawowy.\n\nPakiet od 3 h.", 0, 'fotograf-event.jpg', 'service'],
                ['Opieka nad zwierzętami — wyjazdy i delegacje', "Karmienie, spacery psów, czyszczenie kuwet.\n\nDoświadczenie z kotami i psami średnich ras.\n\nKlucze lub kody pod klienta.", 0, 'opieka-zwierzeta.jpg', 'service'],
                ['Montaż mebli i AGD — IKEA i inne', "Szybki montaż, wypoziomowanie, utylizacja opakowań.\n\nWłasne narzędzia.\n\nWycena po liście mebli.", 0, 'montaz-mebli.jpg', 'service'],
                ['Tłumacz przysięgły — dokumenty urzędowe', "Angielski i niemiecki, apostille, akty stanu cywilnego.\n\nTermin ekspres 24 h po uzgodnieniu.\n\nWycena za stronę standardową.", 0, 'tlumacz-przysiegly.jpg', 'service'],
            ],
        ];
    }
}
