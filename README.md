# Zunto — serwis ogłoszeń drobnych (V1)

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
| `COMPOSE_PROJECT_NAME` | `zunto` | prefiks projektu i nazwa obrazu PHP |
| `APP_PORT` | `8090` | aplikacja (nginx) → http://localhost:8090 |
| `VITE_PORT` | `5174` | Vite dev server |
| `DB_FORWARD_PORT` | `5434` | PostgreSQL wystawiony na `127.0.0.1` |
| `*_CONTAINER_NAME` | `zunto-*` | nazwy kontenerów |

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

Konto administratora z seedera: `admin@zunto.local`, hasło z `UserFactory`: `password`.

### Migracja z poprzedniej nazwy (`ogloszenia`)

Jeśli środowisko działało jeszcze jako „Ogloszenia” / `ogloszenia.gesoft.pl`:

1. Zatrzymaj stare kontenery (`docker compose -p ogloszenia down` albo ręcznie).
2. W `.env` ustaw `APP_NAME=Zunto`, `APP_URL=https://zunto.pl`, `SANCTUM_STATEFUL_DOMAINS=zunto.pl`
   oraz prefiksy Dockera `zunto-*` (patrz `.env.example`).
3. Baza: albo świeża instalacja (`migrate --seed` na pustej bazie `zunto`), albo ręczne
   przemianowanie bazy/użytkownika Postgres (`ogloszenia` → `zunto`) i aktualizacja
   `DB_DATABASE` / `DB_USERNAME` w `.env`.
4. W konsolach OAuth (Google, Facebook) podmień redirect URI na `https://zunto.pl/auth/.../callback`.
5. `docker compose up -d --build` i `php artisan config:clear`.

## Za Nginx Proxy Managerem

Serwis jest wystawiony pod `https://zunto.pl`. NPM (`192.168.88.240`)
przekierowuje na backend (np. `192.168.88.244:8090`).

W zakładce **Advanced** w NPM musi być `client_max_body_size 100M;`, inaczej
wysyłka 10 zdjęć po 8 MB padnie na proxy.

Kluczowe zmienne dla domeny:

| Zmienna | Wartość | Dlaczego |
| --- | --- | --- |
| `APP_URL` | `http://zunto.pl` | inaczej URL-e zdjęć wskazują na `localhost:8090` |
| `SANCTUM_STATEFUL_DOMAINS` | zawiera domenę | bez tego SPA nie zaloguje się w ogóle |
| `SESSION_DOMAIN` | `null` | ciasteczko host-only: działa i na domenie, i na `localhost` |
| `SESSION_SECURE_COOKIE` | `false` | serwis chodzi po `http`; przy HTTPS ustaw `true` |
| `TRUSTED_PROXIES` | `192.168.88.240` | `RateLimiter` kluczuje po IP klienta |
| `APP_DEBUG` | `false` | publiczna strona nie może wystawiać stack trace'ów |

`TRUSTED_PROXIES` musi być dokładnym adresem NPM. Każdy host, który dosięgnie
`192.168.88.244:8090` bezpośrednio, może podszyć się nagłówkiem
`X-Forwarded-For` pod cudze IP i obejść limity zapytań.

Przy włączonym HTTPS w NPM ustaw dodatkowo:

| Zmienna | Wartość | Dlaczego |
| --- | --- | --- |
| `APP_URL` | `https://zunto.pl` | redirect URI OAuth i linki w mailach muszą zgadzać się ze schematem |
| `SESSION_SECURE_COOKIE` | `true` | sesja po logowaniu OAuth nie przeżyje powrotu z Google/Facebook |

## Logowanie przez Google i Facebook

Logowanie społecznościowe działa przez Laravel Socialite. Trasy:

| Adres | Rola |
| --- | --- |
| `/auth/{provider}/redirect` | przekierowanie do Google lub Facebook |
| `/auth/{provider}/callback` | powrót po autoryzacji i zalogowanie sesją Sanctum |

Dozwolone wartości `{provider}`: `google`, `facebook`.

Przyciski na ekranie logowania pojawiają się tylko dla dostawców skonfigurowanych
w `.env`. Lista aktywnych dostawców: `GET /api/v1/auth/oauth-providers`.

### Zmienne w `.env`

```env
GOOGLE_CLIENT_ID=twoj-google-client-id
GOOGLE_CLIENT_SECRET=twoj-google-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

FACEBOOK_CLIENT_ID=twoj-facebook-app-id
FACEBOOK_CLIENT_SECRET=twoj-facebook-secret
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
```

`GOOGLE_REDIRECT_URI` i `FACEBOOK_REDIRECT_URI` można pominąć — wtedy Laravel
zbuduje je z `APP_URL`. Adres musi być **identyczny** z wpisem w konsoli dostawcy
(włącznie ze schematem `http`/`https`).

Po zmianie `.env`:

```bash
docker compose exec php php artisan config:clear
```

### Google Cloud Console

1. Wejdź w [Google Cloud Console](https://console.cloud.google.com/) → **APIs & Services** → **Credentials**.
2. Utwórz **OAuth client ID** typu **Web application**.
3. W **Authorized redirect URIs** dodaj np.:
   - produkcja: `https://zunto.pl/auth/google/callback`
   - lokalnie: `http://localhost:8090/auth/google/callback`
4. Skopiuj **Client ID** i **Client secret** do `GOOGLE_CLIENT_ID` i `GOOGLE_CLIENT_SECRET`.
5. Na ekranie zgody OAuth upewnij się, że aplikacja ma zakresy `email` i `profile`
   (Socialite żąda ich domyślnie).

### Meta for Developers (Facebook)

1. Wejdź w [Meta for Developers](https://developers.facebook.com/) → **My Apps** → utwórz aplikację typu **Consumer**.
2. Dodaj produkt **Facebook Login** → **Settings** → **Valid OAuth Redirect URIs**:
   - produkcja: `https://zunto.pl/auth/facebook/callback`
   - lokalnie: `http://localhost:8090/auth/facebook/callback`
3. W **Settings → Basic** skopiuj **App ID** i **App Secret** do `FACEBOOK_CLIENT_ID`
   i `FACEBOOK_CLIENT_SECRET`.
4. Aplikacja musi mieć uprawnienie `email` — bez niego logowanie kończy się błędem
   „Dostawca nie przekazał adresu e-mail”.
5. W trybie **Development** logować mogą tylko użytkownicy dodani jako testerzy
   aplikacji. Na produkcję wymagana jest weryfikacja aplikacji przez Meta.

### Weryfikacja

```bash
# powinno zwrócić skonfigurowanych dostawców, np. ["google","facebook"]
curl -s http://localhost:8090/api/v1/auth/oauth-providers

docker compose exec php php artisan config:clear
```

Wejdź na `/logowanie` — przyciski Google/Facebook są widoczne tylko dla dostawców
z uzupełnionym `client_id` i `client_secret`. Po udanym logowaniu sesja SPA działa
tak samo jak po logowaniu e-mailem (cookie Sanctum).

## Poczta wychodząca

Domyślnie `MAIL_MAILER=log` — wiadomości trafiają do `storage/logs/laravel.log`
i **nie** wychodzą na zewnątrz. To wystarcza lokalnie; na produkcji ustaw prawdziwy
transport (najczęściej SMTP).

Wszystkie maile transakcyjne idą przez kolejkę `notifications`. Kontener `worker`
musi działać (`docker compose ps worker`), inaczej wiadomości zalegną w Redis.

### Jakie maile wysyła aplikacja

| Zdarzenie | Odbiorca |
| --- | --- |
| Rejestracja konta | link weryfikacyjny e-mail |
| Publikacja / aktywacja ogłoszenia | sprzedawca |
| Wygaśnięcie ogłoszenia | sprzedawca (informacja o możliwości odświeżenia) |
| 5 dni przed usunięciem wygasłego ogłoszenia | sprzedawca |
| Nowa wiadomość w konwersacji | druga strona rozmowy |

### Konfiguracja SMTP (produkcja)

Przykład dla klasycznego serwera pocztowego (OVH, home.pl, własny Postfix itd.):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.twojadomena.pl
MAIL_PORT=587
MAIL_USERNAME=noreply@twojadomena.pl
MAIL_PASSWORD=haslo-lub-app-password
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS="noreply@twojadomena.pl"
MAIL_FROM_NAME="${APP_NAME}"
```

Port `465` zwykle wymaga `MAIL_SCHEME=ssl`. Po zmianie:

```bash
docker compose exec php php artisan config:clear
docker compose restart worker
```

Adres w `MAIL_FROM_ADDRESS` powinien istnieć u dostawcy i mieć poprawne rekordy
SPF/DKIM — inaczej Gmail i Outlook odrzucą lub oznaczą maile jako spam.

### Gmail / Google Workspace (SMTP)

1. Włącz **2-Step Verification** na koncie Google.
2. Utwórz **App Password** (hasło aplikacji) dla „Mail”.
3. W `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=twoj-email@gmail.com
MAIL_PASSWORD=haslo-aplikacji-16-znakow
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS="twoj-email@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Resend lub Postmark (API zamiast SMTP)

Laravel ma gotowe transporty w `config/mail.php`. Wystarczy zmienić mailer i klucz API
w `config/services.php`:

**Resend:**

```env
MAIL_MAILER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS="noreply@zweryfikowana-domena.pl"
```

**Postmark:**

```env
MAIL_MAILER=postmark
POSTMARK_API_KEY=...
MAIL_FROM_ADDRESS="noreply@zweryfikowana-domena.pl"
```

Domena nadawcy musi być zweryfikowana w panelu dostawcy.

### Test wysyłki

Szybki test z kontenera PHP (zamień adres na swój):

```bash
docker compose exec php php artisan tinker --execute="
\Illuminate\Support\Facades\Mail::raw('Test SMTP', fn (\$m) => \$m->to('twoj@email.pl')->subject('Test Zunto'));
"
```

Przy `MAIL_MAILER=log` treść znajdziesz w logu:

```bash
docker compose exec php tail -n 50 storage/logs/laravel.log
```

Pełny test kolejki (np. link aktywacyjny po rejestracji): zarejestruj konto na
`/rejestracja` i sprawdź, czy `worker` przetworzył zadanie (`docker compose logs -f worker`).

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
Po wygaśnięciu właściciel ma kolejne 30 dni na odświeżenie; 5 dni przed trwałym
usunięciem wysyłany jest mail ostrzegawczy (wymaga działającej poczty i `worker`).

Kontener `scheduler` uruchamia co godzinę:

* `ads:expire` — ukrycie ogłoszeń po terminie,
* `ads:warn-deletion` — ostrzeżenie 5 dni przed usunięciem,
* `ads:purge-expired` — trwałe usunięcie po upływie okresu na odświeżenie.

Kontener `worker` obsługuje kolejkę Redis (w tym maile z kolejki `notifications`).
