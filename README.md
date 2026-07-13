# Serwis ogłoszeń drobnych — V1

Laravel 13 (PHP 8.4) + Vue 3 / PrimeVue 4 SPA, PostgreSQL 16, Redis 7.
Wszystko działa w kontenerach Dockera — na hoście nie jest instalowane nic poza Dockerem.

## Uruchomienie

```bash
cp .env.example .env
docker compose build
docker compose up -d
docker compose run --rm php php artisan key:generate
docker compose run --rm php php artisan migrate --seed
docker compose run --rm php php artisan storage:link
docker compose run --rm node npm ci
docker compose run --rm node npm run build
```

## Porty i nazwy kontenerów

Jedno źródło prawdy: **`.env`**. Compose czyta ten plik automatycznie, a Laravel
interpoluje te same zmienne w `APP_URL` i `SANCTUM_STATEFUL_DOMAINS`, więc
zmiana portu w jednym miejscu przestawia jednocześnie Dockera i aplikację.

| Zmienna | Domyślnie | Znaczenie |
| --- | --- | --- |
| `COMPOSE_PROJECT_NAME` | `ogloszenia` | prefiks projektu i nazwa obrazu PHP |
| `APP_PORT` | `8090` | aplikacja (nginx) → http://localhost:8090 |
| `VITE_PORT` | `5174` | Vite dev server |
| `DB_FORWARD_PORT` | `5434` | PostgreSQL wystawiony na `127.0.0.1` |
| `*_CONTAINER_NAME` | `ogloszenia-*` | nazwy kontenerów |

Porty są przesunięte, bo na tej maszynie 8080, 5173, 5432 i 6379 są zajęte przez
inne projekty. Zmiana portu:

```bash
sed -i 's/^APP_PORT=8090/APP_PORT=8091/' .env
docker compose up -d nginx
```

`DB_PORT=5432` to port **wewnątrz** sieci Dockera i nie ma nic wspólnego
z `DB_FORWARD_PORT`. Wyjątek od reguły „wszystko w .env”: `phpunit.xml` ma dane
bazy testowej wpisane wprost, bo XML nie interpoluje zmiennych — zmieniając
`DB_USERNAME`/`DB_PASSWORD` trzeba poprawić też ten plik.

Konto administratora z seedera: `admin@ogloszenia.local`, hasło z `UserFactory`: `password`.

## Za Nginx Proxy Managerem

Serwis jest wystawiony pod `http://ogloszenia.gesoft.pl`. NPM (`192.168.88.240`)
przekierowuje na `192.168.88.244:8090`, schemat `http`.

W zakładce **Advanced** w NPM musi być `client_max_body_size 100M;`, inaczej
wysyłka 10 zdjęć po 8 MB padnie na proxy.

Kluczowe zmienne dla domeny:

| Zmienna | Wartość | Dlaczego |
| --- | --- | --- |
| `APP_URL` | `http://ogloszenia.gesoft.pl` | inaczej URL-e zdjęć wskazują na `localhost:8090` |
| `SANCTUM_STATEFUL_DOMAINS` | zawiera domenę | bez tego SPA nie zaloguje się w ogóle |
| `SESSION_DOMAIN` | `null` | ciasteczko host-only: działa i na domenie, i na `localhost` |
| `SESSION_SECURE_COOKIE` | `false` | serwis chodzi po `http`; przy HTTPS ustaw `true` |
| `TRUSTED_PROXIES` | `192.168.88.240` | `RateLimiter` kluczuje po IP klienta |
| `APP_DEBUG` | `false` | publiczna strona nie może wystawiać stack trace'ów |

`TRUSTED_PROXIES` musi być dokładnym adresem NPM. Każdy host, który dosięgnie
`192.168.88.244:8090` bezpośrednio, może podszyć się nagłówkiem
`X-Forwarded-For` pod cudze IP i obejść limity zapytań.

## Quality gate

Backend:

```bash
docker compose run --rm php composer validate --strict
docker compose run --rm php vendor/bin/pint --test
docker compose run --rm php vendor/bin/phpstan analyse --level=6
docker compose run --rm php php artisan test
```

Frontend:

```bash
docker compose run --rm node npm run lint
docker compose run --rm node npm run typecheck
docker compose run --rm node npm run test:unit
docker compose run --rm node npm run build
```

E2E (Playwright):

```bash
# Smoke API — działa w kontenerze node (bez przeglądarki)
docker compose run --rm node npm run test:e2e

# Pełne testy UI — wymaga przeglądarki; na hoście: npx playwright install-deps chromium
E2E_BASE_URL=http://localhost:8090 npm run test:e2e:browser

# Albo jednorazowo w obrazie Playwright (gdy jest miejsce na dysku):
npm run test:e2e:docker
```

## Drzewo kategorii — Closure Table

Kategorie tworzą drzewo o dowolnej głębokości:

* `categories` — węzeł z `parent_id` (tylko bezpośredni rodzic),
* `category_closure` — wiersz dla każdej pary przodek/potomek wraz z `depth`,
  w tym wiersz `(n, n, 0)` dla samego węzła.

Dzięki temu „wszystkie ogłoszenia w Motoryzacji” to jeden indeksowany JOIN
(`Ad::scopeInCategoryTree`), a nie zapytanie rekurencyjne. Ogłoszenie wskazuje
zawsze na liść (np. `Samochody`); kategoria nadrzędna wynika z closure table,
więc nigdy nie jest przechowywana podwójnie.

Zapis do `category_closure` odbywa się wyłącznie przez
`App\Services\CategoryClosureRepository`, w tej samej transakcji co zapis węzła.
Przeniesienie węzła przenosi całe poddrzewo; cykl (`A` pod własnego potomka)
jest odrzucany błędem `CATEGORY_INVALID_PARENT`.

## Kontrakt błędów API

Błędy domenowe mają wspólną kopertę:

```json
{ "code": "ADS_DAILY_LIMIT_REACHED", "message": "...", "details": { "limit": 5 } }
```

Walidacja (422) używa natywnego kształtu Laravela (`message` + `errors`), który
frontend mapuje na pola formularza (`resources/js/api/client.ts`).

## SEO i kanały RSS

Serwis jest SPA, więc crawler dostaje tylko pierwszą odpowiedź HTML. Meta tagi,
`canonical` i dane strukturalne `schema.org/Product` renderuje więc serwer —
`AdPageController` dla ogłoszeń, `SpaController` dla pozostałych tras.

| Adres | Co oddaje |
| --- | --- |
| `/kategoria/{slug}` | landing page kategorii: własny tytuł, canonical i `BreadcrumbList` |
| `/robots.txt` | generowany; `Sitemap:` bierze się z `APP_URL` |
| `/sitemap.xml` | strony statyczne + kategorie + aktywne ogłoszenia |
| `/feed.xml` | RSS 2.0, najnowsze aktywne ogłoszenia |
| `/feed/{kategoria}.xml` | RSS zawężony do poddrzewa kategorii (closure table) |

### Adresy kategorii

Adres jest **płaski** (`/kategoria/samochody`), a nie zagnieżdżony
(`/kategoria/motoryzacja/samochody`). `categories.slug` jest unikalny globalnie, więc
węzeł rozwiązuje się bez znajomości głębokości, a przeniesienie gałęzi w drzewie przez
administratora nie unieważnia zaindeksowanego URL-a. Zawężenie listingu do poddrzewa
robi `Ad::scopeInCategoryTree` — jeden slug wystarczy na każdym poziomie.

Zmiana nazwy kategorii przebudowuje sluga; stary wpada do `category_slug_histories`
i odtąd oddaje 301. Historyczne `/?category=…&subcategory=…` przekierowuje middleware
`RedirectLegacyCategoryFilters` (wygrywa węzeł głębszy, reszta filtrów zostaje).

Trasy powłoki SPA są **wyliczone** w `config/seo.php`, a nie łapane catch-allem.
Dzięki temu nieistniejący adres kończy się realnym 404, a nie pustą stroną ze
statusem 200. Dodając trasę w `resources/js/router/index.ts`, dopisz ją także tam
— inaczej wejście z paska adresu dostanie 404.

Ogłoszenie, które wygasło lub zostało usunięte, oddaje **410 Gone**; oczekujące na
moderację — 404, bo nigdy nie było publiczne. Zmiana tytułu lub lokalizacji zmienia
sluga, a stary adres wpada do `ad_slug_histories` i odtąd oddaje **301** na nowy.

Filtry listingu (`q`, cena, stan, dostawa, sortowanie) nie trafiają do `canonical` —
zostaje z nich wyłącznie `page`. Bez tego każda kombinacja parametrów byłaby dla
robota osobnym adresem o tej samej treści.

`SiteUrl` buduje adresy kanoniczne z `APP_URL`, nie z hosta żądania. Sitemapa i
kanały RSS są cache'owane globalnie (`SEO_CACHE_TTL`, domyślnie godzina), więc
gdyby URL-e brały się z requestu, jedno wejście po `localhost` zamroziłoby
localhostowe adresy w pliku, który potem pobiera Googlebot.

Zmiana `docker/nginx/default.conf` wymaga odtworzenia kontenera, nie przeładowania
— pojedynczy plik jest bind-mountowany po inode:

```bash
docker compose up -d --force-recreate nginx
```

## Wygasanie ogłoszeń

Ogłoszenie żyje 30 dni od publikacji lub odświeżenia (`config/ads.php`).
Kontener `scheduler` uruchamia `ads:expire` co godzinę; kontener `worker`
obsługuje kolejkę Redis.
