# Widoki aplikacji

## Cel dokumentu

Ten dokument zbiera wszystkie widoki, które może zobaczyć:

- zwykły odwiedzający,
- zalogowany użytkownik,
- użytkownik ze zweryfikowanym e-mailem,
- administrator.

Opis dotyczy aktualnej aplikacji w katalogu `ogloszenia_dev` na branchu `dev` i obejmuje:

- widoki routowane przez Vue Router,
- strony SSR/SEO wystawiane przez Laravel pod własnymi adresami,
- widoki administracyjne dostępne w panelu `/admin`.

Nie uwzględniam tutaj endpointów API, `robots.txt`, `sitemap.xml` ani kanałów RSS jako pełnoprawnych widoków UI, bo nie są to ekrany aplikacji dla użytkownika końcowego.

## Weryfikacja kompletności

Dokument został zweryfikowany względem katalogu:

- `resources/js/views/*.vue`
- `resources/js/views/admin/*.vue`

Stan weryfikacji:

- liczba widoków routowanych znalezionych w kodzie: `24`,
- liczba widoków opisanych w tym pliku: `24`,
- liczba pominiętych plików widoków: `0`.

Dodatkowo sprawdzone zostały:

- `resources/js/router/index.ts` — komplet tras Vue,
- `routes/web.php` — publiczne adresy backendowe i wejścia SEO,
- `config/seo.php` — strony SPA renderowane przez Laravel,
- `resources/js/App.vue` — wspólny layout aplikacji,
- `resources/js/components/layout/AppHeader.vue` — wspólne elementy nawigacji.

## Podział dostępu

### Publiczne

Dostępne bez logowania:

- strona główna,
- lista ogłoszeń,
- listing kategorii,
- szczegóły ogłoszenia,
- profil sprzedawcy,
- logowanie,
- rejestracja,
- przypomnienie hasła,
- reset hasła z linku,
- ekran weryfikacji e-maila,
- regulamin,
- polityka prywatności,
- strona 404.

### Tylko dla zalogowanych

- moje ogłoszenia,
- ulubione,
- lista rozmów,
- szczegóły rozmowy,
- profil użytkownika.

### Tylko dla zalogowanych i zweryfikowanych e-mailowo

- dodawanie ogłoszenia,
- edycja ogłoszenia.

### Tylko dla administratora

- panel administratora `/admin`,
- zakładka ogłoszeń,
- zakładka kategorii,
- zakładka zgłoszeń,
- zakładka ustawień.

## Wspólny layout widoczny w wielu widokach

To nie są osobne trasy, ale są to realne elementy UI, które użytkownik widzi na większości ekranów aplikacji.

### Powłoka aplikacji

- Plik: `resources/js/App.vue`
- Zawartość:
  - globalny nagłówek,
  - nawigacja kategorii poza landing page,
  - główny obszar treści,
  - baner przypominający o weryfikacji e-mail,
  - stopka,
  - globalne toasty,
  - globalny dialog potwierdzeń.

### Nagłówek aplikacji

- Plik: `resources/js/components/layout/AppHeader.vue`
- Widoczny:
  - na wszystkich głównych stronach SPA,
  - z innym wariantem na landing page i na podstronach.
- Zawartość:
  - logo i nazwa marki,
  - wyszukiwarka w nagłówku,
  - przełącznik języka,
  - przełącznik motywu jasny/ciemny,
  - hamburger na mobile,
  - logowanie i rejestracja dla gościa,
  - profil / moje konto dla zalogowanego,
  - ulubione,
  - wiadomości z badge nieprzeczytanych,
  - przejście do panelu admina dla administratora.

### Nawigacja kategorii

- Plik: `resources/js/components/layout/CategoryNav.vue`
- Widoczna:
  - na podstronach poza landing page.
- Zawartość:
  - szybkie przejścia do kategorii,
  - skróty nawigacyjne do przeglądania ogłoszeń.

### Baner weryfikacji e-mail

- Plik: `resources/js/components/auth/EmailVerificationBanner.vue`
- Widoczny:
  - dla zalogowanego, nieweryfikowanego użytkownika,
  - ukryty na samej stronie `/weryfikacja-email`.
- Cel:
  - przypomnienie o konieczności potwierdzenia adresu e-mail,
  - szybkie ponowne wysłanie linku.

### Stopka

- Plik: `resources/js/components/layout/SiteFooter.vue`
- Widoczna:
  - na wszystkich głównych stronach aplikacji.
- Zawartość:
  - branding,
  - podstawowe linki informacyjne,
  - odnośniki do stron formalnych.

## Widoki publiczne

### 1. Landing page

- URL: `/`
- Nazwa trasy: `landing`
- Plik: `resources/js/views/LandingView.vue`
- Kto widzi: każdy
- Cel:
  - wejściowy ekran serwisu,
  - prezentacja kategorii i wyróżnionych ogłoszeń,
  - zachęcenie do przejścia do wyszukiwania lub publikacji.
- Zawartość:
  - sekcja hero,
  - siatka kategorii,
  - sekcja wyróżnionych ogłoszeń,
  - pasek statystyk,
  - sekcja „jak to działa”.
- Stany:
  - ładowanie danych landing page,
  - komunikat błędu,
  - normalny widok z kategoriami i ogłoszeniami.

### 2. Lista ogłoszeń

- URL: `/ogloszenia`
- Nazwa trasy: `listings`
- Plik: `resources/js/views/HomeView.vue`
- Kto widzi: każdy
- Cel:
  - przeglądanie wszystkich ogłoszeń,
  - filtrowanie i sortowanie wyników.
- Zawartość:
  - formularz wyszukiwania,
  - filtr boczny,
  - aktywne filtry,
  - przełącznik układu siatka/lista,
  - sortowanie,
  - paginacja,
  - karty lub wiersze ogłoszeń.
- Stany:
  - skeletony listy,
  - brak wyników,
  - błąd pobierania,
  - pełna lista wyników.

### 3. Lista ogłoszeń w kategorii

- URL: `/kategoria/{slug}`
- Nazwa trasy: `categories.show`
- Plik: `resources/js/views/HomeView.vue`
- Kto widzi: każdy
- Cel:
  - listing zawężony do konkretnej kategorii.
- Różnice względem `/ogloszenia`:
  - breadcrumb kategorii,
  - dynamiczny nagłówek z nazwą kategorii,
  - tytuł strony zależny od ścieżki kategorii.

### 4. Szczegóły ogłoszenia

- URL: `/ogloszenie/{slug}`
- Nazwa trasy: `ads.show`
- Plik: `resources/js/views/AdDetailView.vue`
- Kto widzi: każdy
- Cel:
  - prezentacja pojedynczego ogłoszenia.
- Zawartość:
  - breadcrumb kategorii,
  - galeria zdjęć,
  - tytuł, cena, lokalizacja,
  - opis z rozwijaniem „pokaż więcej / pokaż mniej”,
  - panel dostawy,
  - karta sprzedawcy,
  - metadata ogłoszenia,
  - przycisk ulubionych,
  - modal zgłoszenia naruszenia,
  - modal wysłania wiadomości,
  - sekcja „więcej od sprzedawcy”.
- Zachowania:
  - gość po kliknięciu „napisz” trafia na logowanie,
  - numer telefonu odsłaniany jest na żądanie w komponencie sprzedawcy,
  - przy usuniętym lub nieistniejącym ogłoszeniu pojawia się komunikat błędu.

### 5. Profil sprzedawcy

- URL: `/sprzedawca/{slug}`
- Nazwa trasy: `sellers.show`
- Plik: `resources/js/views/SellerView.vue`
- Kto widzi: każdy
- Cel:
  - publiczna wizytówka sprzedawcy z jego aktywnymi ogłoszeniami.
- Zawartość:
  - avatar lub inicjały,
  - nazwa sprzedawcy,
  - data obecności w serwisie,
  - bio,
  - liczba aktywnych ogłoszeń,
  - lista ogłoszeń sprzedawcy,
  - przełącznik układu siatka/lista,
  - sortowanie,
  - paginacja.
- Stany:
  - ładowanie profilu,
  - błąd „nie znaleziono sprzedawcy”,
  - brak aktywnych ogłoszeń,
  - lista ogłoszeń.

### 6. Logowanie

- URL: `/logowanie`
- Nazwa trasy: `login`
- Plik: `resources/js/views/LoginView.vue`
- Kto widzi: każdy niezalogowany; zalogowany też może wejść ręcznie
- Cel:
  - logowanie hasłem,
  - start logowania społecznościowego.
- Zawartość:
  - pola e-mail i hasło,
  - link „przypomnij hasło”,
  - przycisk logowania,
  - przyciski logowania Google/Facebook,
  - link do rejestracji.
- Obsługiwane komunikaty:
  - błąd logowania,
  - błąd OAuth,
  - informacja o poprawnym resecie hasła po powrocie z `/reset-hasla`.

### 7. Rejestracja

- URL: `/rejestracja`
- Nazwa trasy: `register`
- Plik: `resources/js/views/RegisterView.vue`
- Kto widzi: każdy
- Cel:
  - tworzenie konta lokalnego,
  - rozpoczęcie rejestracji przez Google/Facebook.
- Zawartość:
  - imię i nazwisko / nazwa użytkownika,
  - e-mail,
  - hasło,
  - potwierdzenie hasła,
  - przyciski logowania społecznościowego,
  - linki do regulaminu i polityki prywatności,
  - link do logowania.

### 8. Przypomnienie hasła

- URL: `/przypomnij-haslo`
- Nazwa trasy: `password.forgot`
- Plik: `resources/js/views/ForgotPasswordView.vue`
- Kto widzi: każdy
- Cel:
  - wysłanie linku resetującego hasło.
- Zawartość:
  - pole e-mail,
  - przycisk wysłania linku,
  - link powrotu do logowania.
- Stany:
  - sukces z komunikatem,
  - walidacja pola,
  - błąd wysyłki.

### 9. Reset hasła

- URL: `/reset-hasla?token=...&email=...`
- Nazwa trasy: `password.reset`
- Plik: `resources/js/views/ResetPasswordView.vue`
- Kto widzi: każdy z poprawnym linkiem
- Cel:
  - ustawienie nowego hasła po wejściu z e-maila.
- Zawartość:
  - pole e-mail,
  - nowe hasło,
  - potwierdzenie hasła,
  - przycisk zapisu,
  - link powrotu do logowania.
- Stany:
  - poprawny reset,
  - nieprawidłowy lub niekompletny link,
  - błędy walidacji.

### 10. Weryfikacja adresu e-mail

- URL SPA: `/weryfikacja-email`
- Nazwa trasy: `email.verify`
- Plik: `resources/js/views/EmailVerificationView.vue`
- Powiązana trasa backendowa: `/email/weryfikacja/{id}/{hash}`
- Kto widzi:
  - zalogowany użytkownik oczekujący na potwierdzenie,
  - użytkownik wracający z linku weryfikacyjnego,
  - osoba z niepoprawnym lub wygasłym linkiem.
- Cel:
  - komunikacja statusu weryfikacji.
- Możliwe stany:
  - sukces,
  - oczekiwanie na potwierdzenie,
  - wygasły link,
  - niepoprawny link.
- Akcje:
  - przejście do ogłoszeń,
  - ponowne wysłanie linku,
  - przejście do logowania.

### 11. Regulamin

- URL: `/regulamin`
- Nazwa trasy: `terms`
- Plik: `resources/js/views/TermsView.vue`
- Kto widzi: każdy
- Cel:
  - pełna treść regulaminu serwisu.
- Zawartość:
  - zasady kont,
  - zasady publikacji ogłoszeń,
  - treści zabronione,
  - moderacja,
  - reklamacje,
  - odpowiedzialność,
  - DSA i kwestie formalne.

### 12. Polityka prywatności

- URL: `/polityka-prywatnosci`
- Nazwa trasy: `privacy`
- Plik: `resources/js/views/PrivacyView.vue`
- Kto widzi: każdy
- Cel:
  - pełna informacja o przetwarzaniu danych.
- Zawartość:
  - administrator danych,
  - cele i podstawy przetwarzania,
  - dane publiczne,
  - odbiorcy danych,
  - prawa użytkownika,
  - cookies,
  - bezpieczeństwo,
  - okresy przechowywania.

### 13. Strona 404

- URL: dowolny nieistniejący adres SPA
- Nazwa trasy: `not-found`
- Plik: `resources/js/views/NotFoundView.vue`
- Kto widzi: każdy
- Cel:
  - informacja, że strona nie istnieje.
- Zawartość:
  - tytuł błędu,
  - krótki opis,
  - przycisk powrotu na stronę główną.

## Widoki użytkownika po zalogowaniu

### 14. Dodawanie ogłoszenia

- URL: `/dodaj-ogloszenie`
- Nazwa trasy: `ads.create`
- Plik: `resources/js/views/AdCreateView.vue`
- Kto widzi: zalogowany i zweryfikowany użytkownik
- Cel:
  - publikacja nowego ogłoszenia.
- Zawartość:
  - formularz ogłoszenia,
  - pola wymagane i opcjonalne,
  - wysyłka zdjęć,
  - dane lokalizacji,
  - dane kontaktowe i dostawy.
- Po zapisie:
  - aktywne ogłoszenie kieruje na publiczny detal,
  - ogłoszenie oczekujące kieruje do „Moje ogłoszenia”.

### 15. Edycja ogłoszenia

- URL: `/moje-ogloszenia/{slug}/edytuj`
- Nazwa trasy: `ads.edit`
- Plik: `resources/js/views/AdEditView.vue`
- Kto widzi: zalogowany i zweryfikowany właściciel ogłoszenia
- Cel:
  - edycja istniejącego ogłoszenia.
- Zawartość:
  - ładowanie danych ogłoszenia,
  - formularz edycji,
  - lista istniejących zdjęć,
  - zapis zmian.
- Stany:
  - spinner ładowania,
  - błąd wczytania,
  - formularz gotowy do edycji.

### 16. Moje ogłoszenia

- URL: `/moje-ogloszenia`
- Nazwa trasy: `ads.mine`
- Plik: `resources/js/views/MyAdsView.vue`
- Kto widzi: zalogowany użytkownik
- Cel:
  - zarządzanie własnymi ogłoszeniami.
- Zawartość:
  - tabela ogłoszeń,
  - status ogłoszenia,
  - cena,
  - data dodania,
  - data wygaśnięcia,
  - akcje: edytuj, odśwież, usuń.
- Statusy widoczne:
  - aktywne,
  - oczekujące,
  - odrzucone,
  - wygasłe,
  - usunięte.

### 17. Ulubione

- URL: `/ulubione`
- Nazwa trasy: `favorites`
- Plik: `resources/js/views/FavoritesView.vue`
- Kto widzi: zalogowany użytkownik
- Cel:
  - lista obserwowanych ogłoszeń.
- Zawartość:
  - siatka kart ogłoszeń,
  - przycisk usunięcia z ulubionych,
  - paginacja.
- Stany:
  - ładowanie,
  - błąd,
  - pusty stan,
  - lista zapisanych ofert.

### 18. Lista wiadomości

- URL: `/wiadomosci`
- Nazwa trasy: `messages`
- Plik: `resources/js/views/MessagesView.vue`
- Kto widzi: zalogowany użytkownik
- Cel:
  - przegląd wszystkich rozmów.
- Zawartość:
  - lista rozmów,
  - avatar/inicjały rozmówcy,
  - nazwa rozmówcy,
  - tytuł ogłoszenia,
  - cena ogłoszenia,
  - podgląd ostatniej wiadomości,
  - data ostatniej aktywności,
  - oznaczenie nieprzeczytanych,
  - przycisk „wczytaj starsze rozmowy”.
- Stany:
  - ładowanie,
  - błąd,
  - pusty inbox.

### 19. Szczegóły rozmowy

- URL: `/wiadomosci/{id}`
- Nazwa trasy: `messages.show`
- Plik: `resources/js/views/ConversationView.vue`
- Kto widzi: zalogowany uczestnik rozmowy
- Cel:
  - prowadzenie pojedynczej konwersacji.
- Zawartość:
  - powrót do listy wiadomości,
  - avatar/inicjały rozmówcy,
  - nazwa rozmówcy,
  - kontekst ogłoszenia,
  - cena,
  - wątek wiadomości,
  - formularz odpowiedzi.
- Stany:
  - skeleton rozmowy,
  - błąd / brak rozmowy,
  - pełny czat.

### 20. Profil użytkownika

- URL: `/profil`
- Nazwa trasy: `profile`
- Plik: `resources/js/views/ProfileView.vue`
- Kto widzi: zalogowany użytkownik
- Cel:
  - zarządzanie danymi konta.
- Zawartość:
  - sekcja tożsamości z avatarem,
  - nawigacja do profilu, moich ogłoszeń i dodawania ogłoszenia,
  - status weryfikacji konta,
  - upload avatara,
  - podstawowe dane profilu,
  - bio,
  - numer telefonu,
  - usunięcie avatara,
  - strefa usunięcia konta.
- Akcje:
  - zapis profilu,
  - usunięcie avatara,
  - trwałe usunięcie konta po potwierdzeniu.

## Widoki administracyjne

### 21. Panel administratora

- URL: `/admin`
- Nazwa trasy: `admin`
- Plik: `resources/js/views/admin/AdminView.vue`
- Kto widzi: zalogowany administrator
- Cel:
  - wejście do wszystkich narzędzi administracyjnych.
- Zawartość:
  - nagłówek panelu,
  - zakładki:
    - Ogłoszenia,
    - Kategorie,
    - Zgłoszenia,
    - Ustawienia.

### 22. Panel administratora: Ogłoszenia

- URL: `/admin` zakładka `Ogłoszenia`
- Plik: `resources/js/views/admin/AdminAdsPanel.vue`
- Kto widzi: administrator
- Cel:
  - moderacja i usuwanie ogłoszeń.
- Zawartość:
  - filtr po statusie,
  - tabela ogłoszeń,
  - status ogłoszenia,
  - akcje:
    - zaakceptuj,
    - odrzuć,
    - usuń.
- Dodatkowo:
  - pole powodu odrzucenia dla ogłoszeń oczekujących.

### 23. Panel administratora: Kategorie

- URL: `/admin` zakładka `Kategorie`
- Plik: `resources/js/views/admin/AdminCategoriesPanel.vue`
- Kto widzi: administrator
- Cel:
  - pełne zarządzanie drzewem kategorii.
- Zawartość:
  - drzewo kategorii,
  - wyszukiwarka kategorii,
  - statystyki kategorii,
  - formularz tworzenia/edycji,
  - wybór rodzica,
  - przełącznik widoczności,
  - podgląd sluga,
  - szybkie dodawanie podkategorii,
  - usuwanie kategorii.

### 24. Panel administratora: Zgłoszenia

- URL: `/admin` zakładka `Zgłoszenia`
- Plik: `resources/js/views/admin/AdminReportsPanel.vue`
- Kto widzi: administrator
- Cel:
  - obsługa zgłoszeń naruszeń od użytkowników i gości.
- Zawartość:
  - tabela zgłoszeń,
  - powiązane ogłoszenie,
  - powód zgłoszenia,
  - dodatkowa wiadomość,
  - data zgłoszenia,
  - akcje:
    - oznacz jako rozpatrzone,
    - odrzuć zgłoszenie.

### 25. Panel administratora: Ustawienia

- URL: `/admin` zakładka `Ustawienia`
- Plik: `resources/js/views/admin/AdminSettingsPanel.vue`
- Kto widzi: administrator
- Cel:
  - ustawienia globalne moderacji.
- Zawartość:
  - przełącznik automatycznej akceptacji ogłoszeń,
  - opis działania ustawienia.

## Widoki pośrednie i techniczne, które użytkownik odczuwa, ale nie widzi jako osobnej strony

### Logowanie Google i Facebook

- Trasy backendowe:
  - `/auth/google/redirect`
  - `/auth/google/callback`
  - `/auth/facebook/redirect`
  - `/auth/facebook/callback`
- To nie są osobne widoki aplikacji.
- Użytkownik odczuwa je jako:
  - przejście z logowania lub rejestracji do dostawcy zewnętrznego,
  - powrót do aplikacji i przekierowanie na odpowiedni ekran,
  - ewentualny komunikat błędu na ekranie logowania lub rejestracji.

### Link weryfikacyjny z e-maila

- Trasa backendowa:
  - `/email/weryfikacja/{id}/{hash}`
- To nie jest samodzielny ekran SPA.
- Po wejściu użytkownik finalnie trafia na widok `/weryfikacja-email` z odpowiednim stanem.

## Widoki, które mają znaczenie SEO

Najważniejsze strony indeksowalne i przeznaczone do wejść z Google/Facebook/X:

- `/` — landing page,
- `/ogloszenia` — listing,
- `/kategoria/{slug}` — listing kategorii,
- `/ogloszenie/{slug}` — szczegóły ogłoszenia,
- `/sprzedawca/{slug}` — profil sprzedawcy,
- `/regulamin`,
- `/polityka-prywatnosci`.

Widoki nieindeksowalne z założenia:

- `/logowanie`,
- `/rejestracja`,
- `/przypomnij-haslo`,
- `/reset-hasla`,
- `/weryfikacja-email`,
- `/dodaj-ogloszenie`,
- `/moje-ogloszenia`,
- `/profil`,
- `/admin`.

## Skrócona mapa widoków

| Typ | URL | Trasa | Plik |
|---|---|---|---|
| Publiczny | `/` | `landing` | `LandingView.vue` |
| Publiczny | `/ogloszenia` | `listings` | `HomeView.vue` |
| Publiczny | `/kategoria/{slug}` | `categories.show` | `HomeView.vue` |
| Publiczny | `/ogloszenie/{slug}` | `ads.show` | `AdDetailView.vue` |
| Publiczny | `/sprzedawca/{slug}` | `sellers.show` | `SellerView.vue` |
| Publiczny | `/logowanie` | `login` | `LoginView.vue` |
| Publiczny | `/rejestracja` | `register` | `RegisterView.vue` |
| Publiczny | `/przypomnij-haslo` | `password.forgot` | `ForgotPasswordView.vue` |
| Publiczny | `/reset-hasla` | `password.reset` | `ResetPasswordView.vue` |
| Publiczny | `/weryfikacja-email` | `email.verify` | `EmailVerificationView.vue` |
| Publiczny | `/regulamin` | `terms` | `TermsView.vue` |
| Publiczny | `/polityka-prywatnosci` | `privacy` | `PrivacyView.vue` |
| Publiczny | `*` | `not-found` | `NotFoundView.vue` |
| Zalogowany | `/dodaj-ogloszenie` | `ads.create` | `AdCreateView.vue` |
| Zalogowany | `/moje-ogloszenia` | `ads.mine` | `MyAdsView.vue` |
| Zalogowany | `/moje-ogloszenia/{slug}/edytuj` | `ads.edit` | `AdEditView.vue` |
| Zalogowany | `/ulubione` | `favorites` | `FavoritesView.vue` |
| Zalogowany | `/wiadomosci` | `messages` | `MessagesView.vue` |
| Zalogowany | `/wiadomosci/{id}` | `messages.show` | `ConversationView.vue` |
| Zalogowany | `/profil` | `profile` | `ProfileView.vue` |
| Admin | `/admin` | `admin` | `AdminView.vue` |
| Admin | `/admin` zakładka | `ads` | `AdminAdsPanel.vue` |
| Admin | `/admin` zakładka | `categories` | `AdminCategoriesPanel.vue` |
| Admin | `/admin` zakładka | `reports` | `AdminReportsPanel.vue` |
| Admin | `/admin` zakładka | `settings` | `AdminSettingsPanel.vue` |

## Uwagi końcowe

- Widok `HomeView.vue` obsługuje dwa różne adresy: ogólny listing i listing kategorii.
- Widok `EmailVerificationView.vue` jest ekranem zbiorczym dla kilku stanów procesu weryfikacji.
- Panel administratora ma jeden adres URL, ale zawiera cztery osobne podwidoki funkcyjne.
- Część ekranów publicznych jest przygotowana pod SSR/SEO po stronie Laravel, ale po wejściu użytkownika dalej działa jako SPA Vue.
