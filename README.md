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

Logowanie społecznościowe działa przez Laravel Socialite. Konfiguracja to zawsze te same
trzy kroki: zarejestrowanie aplikacji u dostawcy → wpisanie dwóch kluczy do `.env` →
`config:clear`.

### Jak to działa

| Adres | Rola |
| --- | --- |
| `/auth/{provider}/redirect` | wysyła użytkownika do dostawcy (limit 10 zapytań/min) |
| `/auth/{provider}/callback` | powrót po autoryzacji: logowanie sesją i redirect na `/` |
| `GET /api/v1/auth/oauth-providers` | lista dostawców, dla których SPA pokazuje przyciski |

`{provider}` to wyłącznie `google` albo `facebook` — każda inna wartość daje 404.

Dostawca liczy się jako skonfigurowany dopiero wtedy, gdy **oba** klucze — `client_id`
i `client_secret` — są niepuste (`App\Support\OAuthConfigurator`). Do tego czasu przycisk
nie pojawia się na ekranie logowania w ogóle, a wejście na `/auth/google/redirect` z paska
adresu wraca na `/logowanie?oauth_error=unconfigured`.

### Krok 1 — Google Cloud Console

1. Wejdź na [console.cloud.google.com](https://console.cloud.google.com/) i wybierz (lub utwórz) projekt.
2. **APIs & Services → OAuth consent screen**:
   * typ **External**, nazwa aplikacji, e-mail wsparcia i kontaktowy,
   * zakresy: `email`, `profile`, `openid` — Socialite prosi o nie domyślnie,
   * dopóki aplikacja jest w trybie **Testing**, zalogują się wyłącznie konta dopisane
     w sekcji **Test users**. Zdejmuje to dopiero **Publish app**.
3. **APIs & Services → Credentials → Create credentials → OAuth client ID**:
   * typ aplikacji: **Web application**,
   * **Authorized redirect URIs** — adres musi być identyczny z `GOOGLE_REDIRECT_URI`
     co do znaku (schemat, host, port, bez ukośnika na końcu):
     * produkcja: `https://zunto.pl/auth/google/callback`
     * lokalnie: `http://localhost:8090/auth/google/callback`
4. Skopiuj **Client ID** i **Client secret**.

Rozjazd choćby o jeden znak (`http` zamiast `https`, brak portu, `www.`) kończy się
błędem `redirect_uri_mismatch` jeszcze po stronie Google — aplikacja nawet nie dostanie żądania.

### Krok 2 — Meta for Developers (Facebook)

1. Wejdź na [developers.facebook.com](https://developers.facebook.com/) → **My Apps → Create App**
   i wybierz przypadek użycia **Authenticate and request data from users with Facebook Login**.
2. Dodaj produkt **Facebook Login → Settings → Valid OAuth Redirect URIs**:
   * produkcja: `https://zunto.pl/auth/facebook/callback`
   * lokalnie: `http://localhost:8090/auth/facebook/callback`

   Facebook dopuszcza `http://` **tylko** dla `localhost`. Każdy inny host musi mieć HTTPS,
   więc na serwerze testowym po `http://` logowanie przez Facebooka nie zadziała.
3. **App settings → Basic**: **App ID** → `FACEBOOK_CLIENT_ID`, **App secret** → `FACEBOOK_CLIENT_SECRET`.
4. Aplikacja musi prosić o uprawnienie `email`. Konto Facebooka bez adresu (np. założone na
   numer telefonu) wraca na `/logowanie?oauth_error=email_required` — bez adresu nie zakładamy konta.
5. W trybie **Development** zalogują się tylko osoby z rolą w aplikacji (administrator,
   deweloper, tester). Publiczne logowanie wymaga trybu **Live** i weryfikacji aplikacji przez Meta.

### Krok 3 — `.env`

```env
GOOGLE_CLIENT_ID=123456789-abc.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-...
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

FACEBOOK_CLIENT_ID=1234567890123456
FACEBOOK_CLIENT_SECRET=...
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
```

Obie zmienne `*_REDIRECT_URI` można pominąć — wtedy Laravel złoży adres z `APP_URL`
(`config/services.php`). Cokolwiek wybierzesz, wynik musi zgadzać się z wpisem w konsoli
dostawcy, dlatego **najpierw ustaw poprawne `APP_URL`**.

Po każdej zmianie `.env`:

```bash
docker compose exec php php artisan config:clear
```

### Krok 4 — sprawdzenie

```bash
# ["google","facebook"] — dostawcy z kompletem kluczy
curl -s http://localhost:8090/api/v1/auth/oauth-providers
```

Wejdź na `/logowanie` — przyciski są widoczne tylko dla dostawców z powyższej listy.
Po udanym logowaniu sesja SPA działa tak samo jak po logowaniu e-mailem (cookie Sanctum),
a konto zakładane przez OAuth ma adres e-mail od razu potwierdzony.

### Gdy logowanie się nie udaje

Błąd wraca zawsze jako `/logowanie?oauth_error=…` i SPA zamienia go na komunikat:

| `oauth_error` | Znaczenie | Co sprawdzić |
| --- | --- | --- |
| `unconfigured` | brak `client_id` lub `client_secret` | wpisy w `.env` + `php artisan config:clear` |
| `email_required` | dostawca nie oddał adresu e-mail | uprawnienie `email` w aplikacji Facebooka |
| `failed` | wyjątek w callbacku (zły secret, wygasły kod, brak sesji) | `docker compose logs php` — wpis `OAuth callback failed` |

Najczęstsza przyczyna `failed` na produkcji to zgubiona sesja w drodze do dostawcy i z powrotem:
przy HTTPS musi być `SESSION_SECURE_COOKIE=true`, a `APP_URL` musi mieć schemat `https`.

## Poczta wychodząca

### Czym maile jadą

Wszystkie wiadomości to kolejkowane notyfikacje (`ShouldQueue`) na kolejce `notifications`.
Droga jednej wiadomości wygląda tak:

```
żądanie (np. rejestracja) → Redis, kolejka `notifications`
    → kontener `worker` (queue:work --queue=notifications,default)
        → transport wskazany przez MAIL_MAILER
```

Wniosek praktyczny: **bez działającego kontenera `worker` nie wyjdzie żaden mail** —
zadania będą po prostu leżeć w Redisie. Sprawdzenie: `docker compose ps worker`.

| Zdarzenie | Odbiorca |
| --- | --- |
| Rejestracja konta | link aktywacyjny na adres użytkownika |
| Publikacja / aktywacja ogłoszenia | sprzedawca |
| Wygaśnięcie ogłoszenia | sprzedawca (może odświeżyć) |
| 5 dni przed trwałym usunięciem | sprzedawca |
| Nowa wiadomość w konwersacji | druga strona rozmowy |

### Domyślnie: `log` (nic nie wychodzi na zewnątrz)

`MAIL_MAILER=log` zapisuje treść maila do `storage/logs/laravel.log`. To wystarcza lokalnie —
link aktywacyjny wyciągniesz z loga:

```bash
docker compose exec php sh -c \
  "grep -o 'http[^\"]*email/weryfikacja[^\"]*' storage/logs/laravel.log | tail -1"
```

### SMTP — typowa konfiguracja produkcyjna

Klucze `MAIL_HOST`…`MAIL_SCHEME` są w `.env.example` zakomentowane; odkomentuj je i uzupełnij.

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.twojadomena.pl
MAIL_PORT=587
MAIL_USERNAME=noreply@twojadomena.pl
MAIL_PASSWORD=haslo-lub-haslo-aplikacji
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS="noreply@twojadomena.pl"
MAIL_FROM_NAME="${APP_NAME}"
```

* port **587** → `MAIL_SCHEME=tls` (STARTTLS),
* port **465** → `MAIL_SCHEME=ssl`,
* `MAIL_FROM_ADDRESS` musi być adresem istniejącym u dostawcy i mieć poprawne rekordy
  **SPF** i **DKIM** — inaczej Gmail i Outlook wrzucą maile do spamu albo je odrzucą.

Po zmianie zawsze:

```bash
docker compose exec php php artisan config:clear
docker compose restart worker   # worker trzyma starą konfigurację w pamięci
```

Restart `worker`a to nie formalność: `queue:work` czyta konfigurację raz, przy starcie,
więc bez restartu maile dalej lecą starym transportem.

### Gmail / Google Workspace

1. Włącz **weryfikację dwuetapową** na koncie Google.
2. [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords) → utwórz
   **hasło aplikacji** (16 znaków). Zwykłe hasło do konta **nie** zadziała.
3. W `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=twoj-adres@gmail.com
MAIL_PASSWORD=xxxxxxxxxxxxxxxx
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS="twoj-adres@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Gmail nadpisuje nadawcę adresem konta, więc `MAIL_FROM_ADDRESS` musi być tym samym adresem
(albo aliasem dodanym w „Wyślij jako”). Limit darmowego konta to ok. 500 maili dziennie.

### Resend / Postmark / SES (API zamiast SMTP)

Transporty są wbudowane w Laravela; klucze czyta `config/services.php`.

```env
# Resend
MAIL_MAILER=resend
RESEND_API_KEY=re_...

# Postmark
MAIL_MAILER=postmark
POSTMARK_API_KEY=...

# Amazon SES
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=eu-central-1
```

W każdym z tych wariantów domena z `MAIL_FROM_ADDRESS` musi być zweryfikowana w panelu dostawcy.

### Link aktywacyjny — czego wymaga

Po rejestracji SPA zostaje na `/rejestracja` i pokazuje ekran „Konto założone” z prośbą
o kliknięcie linku z maila. Sam link to **podpisany** adres `/email/weryfikacja/{id}/{hash}`:

* budowany jest z **`APP_URL`**, nie z hosta żądania — przy `APP_URL=http://localhost:8090`
  mail wysłany do prawdziwego użytkownika będzie zawierał link do jego własnego localhosta;
* ważny przez `AUTH_VERIFICATION_EXPIRE` minut (domyślnie 60); po tym czasie użytkownik
  ląduje na ekranie „Link wygasł” i prosi o nowy;
* zmiana `APP_URL` po wysłaniu maila unieważnia podpis wszystkich linków będących w drodze;
* zmiana adresu e-mail użytkownika też je unieważnia (hash liczony jest z adresu);
* po kliknięciu backend przenosi na `AUTH_VERIFICATION_REDIRECT_PATH` (`/weryfikacja-email`)
  ze statusem w query stringu;
* ponowna wysyłka (przycisk „Wyślij link ponownie”) to `POST /api/v1/auth/email/verification-notification`,
  z limitem 6 żądań na minutę na użytkownika.

Konto niepotwierdzone działa — nie da się z niego tylko dodawać ani edytować ogłoszeń.

### Test wysyłki

```bash
# 1. Czy transport w ogóle działa (zamień adres na swój)
docker compose exec php php artisan tinker --execute="
\Illuminate\Support\Facades\Mail::raw('Test Zunto', fn (\$m) => \$m->to('twoj@email.pl')->subject('Test'));
"

# 2. Pełna droga przez kolejkę: zarejestruj konto na /rejestracja i patrz, czy worker przetworzył zadanie
docker compose logs -f worker

# 3. Co poszło nie tak
docker compose exec php tail -n 50 storage/logs/laravel.log
```

Punkt 1 omija kolejkę (`Mail::raw` idzie synchronicznie), więc rozdziela dwa problemy:
jeśli test przechodzi, a mail z rejestracji nie dociera — winna jest kolejka, nie SMTP.

### Gdy maile nie dochodzą

| Objaw | Najczęstsza przyczyna |
| --- | --- |
| Nic nie wychodzi, w logu widać treść maila | wciąż `MAIL_MAILER=log` |
| `Mail::raw` działa, mail z rejestracji nie | `worker` nie działa albo trzyma starą konfigurację (`docker compose restart worker`) |
| Zmiany w `.env` bez efektu | brak `php artisan config:clear` |
| Mail dociera, ale link prowadzi w złe miejsce | `APP_URL` nie jest publicznym adresem serwisu |
| Link daje „Nieprawidłowy link” | `APP_URL` albo `APP_KEY` zmieniły się po wysłaniu maila (podpis nie zgadza się) |
| Maile lądują w spamie | brak SPF/DKIM dla domeny z `MAIL_FROM_ADDRESS` |

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
