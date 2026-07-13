# Prompt: przeprojektowanie widoków wyszukiwania i listowania ogłoszeń

Poniższy tekst wklej jako zadanie dla AI.

---

## Kontekst

Pracujesz w istniejącym repozytorium `/var/www/ogloszenia_dev` — serwis ogłoszeń
drobnych. Backend (Laravel 13) jest **gotowy, przetestowany i nietykalny**.
Twoje zadanie dotyczy wyłącznie warstwy prezentacji.

Cały stack działa w Dockerze. Nic nie instaluj na hoście.

```bash
docker compose run --rm node npm run lint       # ESLint 9, zero ostrzeżeń
docker compose run --rm node npm run typecheck  # vue-tsc, strict
docker compose run --rm node npm run test:unit  # Vitest
docker compose run --rm node npm run build      # Vite
```

Aplikacja jest podniesiona pod `http://localhost:8090` oraz
`http://ogloszenia.gesoft.pl`. Konto testowe: `jan@example.com` /
`sekretne-haslo-123`. Admin: `admin@ogloszenia.local` / `password`.

## Cel

Obecne widoki są surowe i nieprzemyślane. Zbuduj **prosty, ale nowoczesny,
mobile-first** interfejs wyszukiwania i listowania ogłoszeń. Ma wyglądać jak
współczesny serwis ogłoszeniowy, nie jak domyślny szablon frameworka.

Zakres do przeprojektowania:

1. `resources/js/views/HomeView.vue` — lista ogłoszeń z wyszukiwaniem
2. `resources/js/components/AdSearchForm.vue` — pasek wyszukiwania i filtry
3. `resources/js/components/AdCard.vue` — kafelek ogłoszenia
4. `resources/js/views/AdDetailView.vue` — szczegóły ogłoszenia
5. `resources/js/App.vue` — nagłówek, stopka, nawigacja
6. `resources/css/app.css` — warstwa designu (tokeny, skala typografii)

Możesz dodawać nowe komponenty i composable'e w `resources/js/`.

## Twarde ograniczenia

**Nie wolno zmieniać:** `app/`, `routes/`, `database/`, `config/`,
`bootstrap/app.php`, `docker-compose.yml`, `.env`. Kontrakt API jest zamrożony.

**Stack, wersje pinowane:**

* Vue 3.5+, wyłącznie `<script setup lang="ts">`, Composition API. Zero Options API.
* PrimeVue **4.5.5** — nie podnoś do v5. Wersja 5 wymaga płatnej licencji
  komercyjnej; 4.5.5 to ostatnia wersja na MIT. Repozytorium PrimeVue na GitHubie
  jest zarchiwizowane (czerwiec 2026), rozwój przeniesiono pod PrimeUI.
* Tailwind CSS 4 (już zainstalowany, `@import 'tailwindcss'`).
* TypeScript strict: `noUncheckedIndexedAccess`, `verbatimModuleSyntax`,
  `noUnusedLocals`. Zero `any` bez komentarza uzasadniającego.
* Pinia setup stores. `storeToRefs()` przy destrukturyzacji.

**PrimeVue 4 + Tailwind 4 współistnieją przez `cssLayer`** — konfiguracja jest
już w `resources/js/app.ts` (`order: 'theme, base, primevue'`), dzięki czemu
klasy utility nadpisują style komponentów. Nie ruszaj tego bez powodu.
Nie nadpisuj CSS komponentów PrimeVue selektorami — używaj **design tokenów**
(`definePreset`) albo **Pass Through API** dla pojedynczych instancji.

**Aktualne nazwy komponentów PrimeVue 4** (nie v3): `Select` (nie `Dropdown`),
`Drawer` (nie `Sidebar`), `DatePicker` (nie `Calendar`), `Popover` (nie `OverlayPanel`).

## Limity jakościowe

* plik `.vue`: maks. 250 linii, `<script setup>` maks. 120, `<template>` maks. 100
* composable `.ts`: maks. 150 linii, store: maks. 180
* funkcja: maks. 30 linii, maks. 4 parametry
* maks. 8 propsów na komponent
* zero logiki biznesowej w `.vue` — wyciągaj do composable'i
* `<style scoped>` albo klasy utility; nic globalnego poza `app.css`

## SOLID na froncie

* **Single Responsibility** — `AdCard` renderuje kafelek i nic więcej. Filtrowanie,
  paginacja i pobieranie danych to osobne composable'e lub store.
* **Open/Closed** — warianty kafelka (siatka vs lista) przez propsy i sloty,
  nie przez `v-if` rozsiane po szablonie.
* **Dependency Inversion** — composable przyjmuje zależność parametrem:
  `export function useAdSearch(api: AdsApi = defaultAdsApi)`. Dzięki temu
  testujesz bez mockowania modułów.
* **DRY z regułą trzech** — dopiero trzecie powtórzenie wydziel do wspólnego kodu.

## Inspiracja

Przejrzyj gotowe szablony ogłoszeniowe / marketplace na **Envato (ThemeForest)**
oraz darmowe [Sakai](https://sakai.primevue.org/) i [Freya](https://freya.primevue.org/).
Szukaj rozwiązań układu, hierarchii typograficznej i gęstości informacji.

**Nie kopiuj kodu ani zasobów z płatnych szablonów** — to licencyjne pole minowe.
Odtwórz wzorce własnymi komponentami.

## Czego oczekuję od projektu

**Mobile-first.** Zacznij od 360 px, potem rozszerzaj. Pasek wyszukiwania na
telefonie zwinięty do jednego pola + przycisk otwierający `Drawer` z filtrami.
Na desktopie filtry inline.

**Wyszukiwanie:**

* pole „Czego szukasz?" z `debounce` ~300 ms i aktualizacją query stringa w URL
  (wyszukiwanie ma być linkowalne i przetrwać odświeżenie — tak już działa `HomeView`)
* wybór kategorii i subkategorii (drzewo pochodzi z closure table, patrz niżej)
* aktywne filtry jako **usuwalne chipy** nad wynikami
* licznik wyników i przycisk „wyczyść filtry"

**Lista:**

* siatka responsywna: 1 kolumna na telefonie, 2 na tablecie, 3–4 na desktopie
* przełącznik siatka / lista, zapamiętany w `localStorage`
* **skeletony** podczas ładowania, nie spinner na środku
* dopracowany **stan pusty** (ilustracja/ikona + sugestia, co zrobić dalej)
* stan błędu z przyciskiem „spróbuj ponownie"

**Kafelek ogłoszenia:**

* zdjęcie z `aspect-ratio`, `loading="lazy"`, sensowny placeholder gdy brak zdjęcia
* tytuł przycięty do 2 linii, cena wyeksponowana
* „Cena do uzgodnienia" gdy `price === null` (0 zł to **nie** to samo co brak ceny)
* lokalizacja i data dodania jako informacje drugorzędne
* cały kafelek klikalny, ale z poprawnym focus ringiem

**Dostępność i wydajność:**

* kontrast WCAG AA, widoczny focus, `aria-label` na przyciskach ikonowych
* dark mode przez `darkModeSelector: '.dark'` (już skonfigurowany) — obie wersje mają wyglądać dobrze
* bez layout shiftu: rezerwuj miejsce na obrazki
* `prefers-reduced-motion` respektowany

## Kontrakt API (zamrożony)

```
GET /api/v1/categories                  → drzewo: widoczne roots + ich children
GET /api/v1/ads?q=&category=&subcategory=&page=   → paginowana lista
GET /api/v1/ads/{slug}                  → szczegóły ogłoszenia
```

Typy są w `resources/js/types/api.ts` i **muszą zgadzać się z Laravel Resources**.
Jeśli potrzebujesz nowego pola — nie dodawaj go, backend jest zamrożony.

Ważne o kategoriach: to **closure table**, nie płaska lista. Kategoria ma
`parent_id`, opcjonalne `children` oraz `ancestors` (najbliższy przodek pierwszy).
Filtr `category=motoryzacja` zwraca też ogłoszenia z `samochody` — czyli
całe poddrzewo. Ogłoszenie wisi zawsze na **liściu**. Breadcrumb w szczegółach
buduj z `ancestors`, odwracając kolejność.

Obsługa błędów, już zaimplementowana w `resources/js/api/client.ts`:

* `422` → błędy pól, kształt `{ message, errors: { pole: [...] } }`
* błędy domenowe → koperta `{ code, message, details }`, np. `AD_NOT_REFRESHABLE`
* `401` → przekierowanie na logowanie, `403` → toast, `429` → komunikat o limicie

Używaj `validationErrors()` i `errorMessage()` z tego modułu. Nie duplikuj.

## Testy

Vitest + `@vue/test-utils`. Testuj **zachowanie, nie implementację**.

Istniejący `resources/js/components/AdCard.spec.ts` opiera się na klasach
`.card__image--empty` itd. Jeśli zmienisz strukturę — **zaktualizuj test**,
nie usuwaj go. Musi nadal sprawdzać: brak ceny → „Cena do uzgodnienia",
brak zdjęcia → placeholder, brak lokalizacji → brak sekcji lokalizacji.

Dopisz testy dla nowych composable'i (debounce, chipy filtrów, przełącznik widoku).
Wyścigi żądań: `resources/js/stores/ads.spec.ts` ma już test, że wolniejsza
odpowiedź nie nadpisuje nowszej — nie zepsuj tego.

## Definicja ukończenia

Nie ogłaszaj gotowości, dopóki **wszystkie cztery komendy** nie przechodzą:

```bash
docker compose run --rm node npm run lint
docker compose run --rm node npm run typecheck
docker compose run --rm node npm run test:unit
docker compose run --rm node npm run build
```

Następnie **uruchom aplikację i obejrzyj ją naprawdę** — nie poprzestawaj na
zielonych testach. Sprawdź w przeglądarce lub `curl`em:

* stronę główną na szerokości 360 px i 1440 px
* wyszukiwanie po frazie, filtr po kategorii i po subkategorii
* stan pusty (fraza bez wyników) i stan ładowania
* ogłoszenie bez zdjęcia i bez ceny
* tryb ciemny

Na koniec napisz krótko: co zmieniłeś, jakie decyzje projektowe podjąłeś i dlaczego,
oraz co świadomie zostawiłeś poza zakresem.

Jeśli któraś komenda nie przechodzi — napisz wprost która i dlaczego.
Nie twierdź, że jest gotowe, jeśli nie znasz wyniku testów.
