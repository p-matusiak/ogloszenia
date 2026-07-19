# Widoki aplikacji — specyfikacja techniczna pod aplikację mobilną

## Cel dokumentu

Ten dokument opisuje ekrany aplikacji z perspektywy implementacji klienta mobilnego.

Dla każdego ekranu podane są:

- typ dostępu,
- źródło nawigacji,
- parametry wejściowe,
- główne komponenty / sekcje UI,
- źródła danych,
- akcje użytkownika,
- stany,
- zależności od sesji, weryfikacji e-mail i roli admina.

Dokument bazuje na aktualnym kodzie:

- `resources/js/router/index.ts`
- `resources/js/views/**/*.vue`
- `resources/js/api/modules/v1/*.ts`
- `routes/web.php`
- `routes/api.php`

## Globalna architektura klienta

### Główna powłoka aplikacji

- Plik: `resources/js/App.vue`
- Elementy wspólne:
  - `AppHeader`
  - `CategoryNav` poza landing page
  - `EmailVerificationBanner` poza ekranem `email.verify`
  - `RouterView`
  - `SiteFooter`
  - `Toast`
  - `ConfirmDialog`

### Globalne dane inicjalizowane po starcie

- `auth.resolve()` — ustalenie sesji użytkownika
- `categories.load()` — wczytanie drzewa kategorii
- `useTheme().initialise()` — motyw jasny/ciemny

### Główne strażniki nawigacji

- `requiresAuth`
- `requiresVerified`
- `requiresAdmin`

### Zachowanie mobilne, które trzeba zachować

- hamburger jako główna nawigacja mobile,
- pełna szerokość listingu i detalu w mobilnym układzie,
- komunikaty błędów i pustych stanów jako pełne stany ekranów, nie tylko toast.

## Kontrakt backendowy używany przez klienta

### Moduły API używane przez ekrany

- `ads.ts`
- `auth.ts`
- `categories.ts`
- `conversations.ts`
- `favorites.ts`
- `profile.ts`
- `sellers.ts`
- `admin.ts`

### Główne grupy endpointów

- publiczne:
  - `GET /api/v1/categories`
  - `GET /api/v1/ads`
  - `GET /api/v1/ads/{slug}`
  - `GET /api/v1/ads/{slug}/more-from-seller`
  - `GET /api/v1/sellers/{slug}`
  - `POST /api/v1/ads/{slug}/reports`
  - `POST /api/v1/ads/{slug}/phone`
  - `GET /api/v1/auth/oauth-providers`
- gość:
  - `POST /api/v1/auth/register`
  - `POST /api/v1/auth/login`
  - `POST /api/v1/auth/forgot-password`
  - `POST /api/v1/auth/reset-password`
- zalogowany:
  - `POST /api/v1/auth/logout`
  - `GET /api/v1/auth/me`
  - `POST /api/v1/auth/profile`
  - `DELETE /api/v1/auth/account`
  - `POST /api/v1/auth/email/verification-notification`
  - `GET /api/v1/my/ads`
  - `GET /api/v1/my/favorites`
  - `GET /api/v1/my/favorites/ids`
  - `POST /api/v1/ads/{slug}/favorite`
  - `DELETE /api/v1/ads/{slug}/favorite`
  - `GET /api/v1/my/conversations`
  - `GET /api/v1/my/conversations/unread-count`
  - `GET /api/v1/conversations/{id}`
  - `GET /api/v1/conversations/{id}/messages`
  - `POST /api/v1/conversations/{id}/messages`
  - `POST /api/v1/ads/{slug}/messages`
  - `DELETE /api/v1/ads/{slug}`
- zalogowany + zweryfikowany:
  - `POST /api/v1/ads/suggest-category`
  - `POST /api/v1/ads`
  - `POST /api/v1/ads/{slug}`
  - `POST /api/v1/ads/{slug}/refresh`
- admin:
  - `GET /api/v1/admin/ads`
  - `POST /api/v1/admin/ads/{slug}/approve`
  - `POST /api/v1/admin/ads/{slug}/reject`
  - `DELETE /api/v1/admin/ads/{slug}`
  - `GET /api/v1/admin/categories`
  - `POST /api/v1/admin/categories`
  - `PUT /api/v1/admin/categories/{slug}`
  - `DELETE /api/v1/admin/categories/{slug}`
  - `GET /api/v1/admin/reports`
  - `PUT /api/v1/admin/reports/{id}`
  - `GET /api/v1/admin/settings`
  - `PUT /api/v1/admin/settings`

## Ekrany publiczne

### 1. Landing

- Route: `landing`
- URL: `/`
- Plik: `resources/js/views/LandingView.vue`
- Dostęp: publiczny
- Parametry wejściowe: brak
- Sekcje UI:
  - `LandingHero`
  - `LandingCategoryGrid`
  - `LandingFeaturedAds`
  - `LandingStatsBar`
  - `LandingHowItWorks`
  - `Message` dla błędu
- Dane:
  - `useLandingPage().load()`
  - wewnętrznie korzysta z kategorii i wyróżnionych ogłoszeń
- Akcje:
  - wejście do kategorii
  - wejście do ogłoszenia
  - przejście do wyszukiwarki lub publikacji
- Stany:
  - `isLoading`
  - `error`
  - pełna treść
- Wskazówki dla mobile:
  - ten ekran może być mocno sekcyjny i scrollowalny,
  - warto trzymać osobne komponenty sekcji również w aplikacji natywnej.

### 2. Listing ogłoszeń

- Route: `listings`
- URL: `/ogloszenia`
- Plik: `resources/js/views/HomeView.vue`
- Dostęp: publiczny
- Parametry wejściowe:
  - query przez `routeFilters()`
  - możliwe m.in. `q`, `location`, `price_min`, `price_max`, `condition`, `delivery`, `sort`, `page`, `seller`
- Sekcje UI:
  - breadcrumb opcjonalny
  - nagłówek listy
  - `AdSearchForm`
  - `FilterSidebar`
  - `ActiveFilters`
  - `SelectButton` układu
  - `Select` sortowania
  - lista `AdCard` albo `AdListItem`
  - `Paginator`
  - `EmptyState`
  - `Message` błędu
- Dane:
  - `categories.load()`
  - `adsStore.search(filters)`
- Główne endpointy:
  - `GET /api/v1/ads`
  - opcjonalnie `GET /api/v1/sellers/{slug}` gdy listing filtrowany po sprzedawcy
- Akcje:
  - zmiana filtrów
  - zmiana układu
  - zmiana sortowania
  - wejście w detal ogłoszenia
  - otwarcie filtrów w drawerze mobile
- Stany:
  - skeleton siatki
  - skeleton listy
  - pusty stan
  - błąd
  - wyniki
- Uwagi mobilne:
  - kluczowy ekran dla aplikacji natywnej,
  - filtry powinny działać jako fullscreen modal / bottom sheet,
  - układ listowy i gridowy powinny być osobnymi rendererami.

### 3. Listing kategorii

- Route: `categories.show`
- URL: `/kategoria/{slug}`
- Plik: `resources/js/views/HomeView.vue`
- Dostęp: publiczny
- Parametry wejściowe:
  - `params.slug`
  - te same query co listing globalny
- Różnice wobec listingu:
  - breadcrumb kategorii,
  - dynamiczny tytuł według kategorii,
  - listing ograniczony przez kategorię.

### 4. Detal ogłoszenia

- Route: `ads.show`
- URL: `/ogloszenie/{slug}`
- Plik: `resources/js/views/AdDetailView.vue`
- Dostęp: publiczny
- Parametry wejściowe:
  - `params.slug`
- Sekcje UI:
  - breadcrumb kategorii
  - `AdGallery`
  - tytuł
  - cena
  - lokalizacja
  - `FavoriteButton`
  - `AdDeliveryPanel`
  - opis z expand/collapse
  - `SellerCard`
  - `AdMetaPanel`
  - sekcja `moreFromSeller`
  - `Dialog` zgłoszenia
  - `Dialog` wysłania wiadomości
- Dane:
  - `fetchAd(slug)`
  - `fetchMoreFromSeller(slug)`
- Główne endpointy:
  - `GET /api/v1/ads/{slug}`
  - `GET /api/v1/ads/{slug}/more-from-seller`
  - `POST /api/v1/ads/{slug}/reports`
  - `POST /api/v1/ads/{slug}/phone`
  - `POST /api/v1/ads/{slug}/messages`
  - `POST /api/v1/ads/{slug}/favorite`
  - `DELETE /api/v1/ads/{slug}/favorite`
- Akcje:
  - zmiana zdjęcia w galerii
  - dodanie/usunięcie z ulubionych
  - pokazanie telefonu
  - wysłanie wiadomości
  - zgłoszenie naruszenia
  - przejście do profilu sprzedawcy
  - przejście do innych ogłoszeń sprzedawcy
- Stany:
  - `AdDetailSkeleton`
  - błąd / brak ogłoszenia
  - aktywny detal
- Zachowania specjalne:
  - gość klikający „napisz” jest przekierowany do logowania,
  - przy usuniętym lub nieistniejącym ogłoszeniu ekran przechodzi w error state.

### 5. Profil sprzedawcy

- Route: `sellers.show`
- URL: `/sprzedawca/{sellerSlug}`
- Plik: `resources/js/views/SellerView.vue`
- Dostęp: publiczny
- Parametry wejściowe:
  - `params.sellerSlug`
  - query listingu
- Sekcje UI:
  - hero sprzedawcy
  - avatar / inicjały
  - nazwa
  - bio
  - data obecności
  - liczba aktywnych ogłoszeń
  - listing ogłoszeń sprzedawcy
  - przełącznik układu
  - sortowanie
  - paginacja
- Dane:
  - `fetchSeller(sellerSlug)`
  - `adsStore.search({ ...filters, seller })`
- Główne endpointy:
  - `GET /api/v1/sellers/{slug}`
  - `GET /api/v1/ads`
- Stany:
  - loading profilu
  - błąd profilu
  - loading listingu
  - pusty stan
  - lista ogłoszeń

### 6. Logowanie

- Route: `login`
- URL: `/logowanie`
- Plik: `resources/js/views/LoginView.vue`
- Dostęp: publiczny
- Parametry wejściowe:
  - query `redirect`
  - query `email`
  - query `reset=1`
  - query `oauth_error`
- Sekcje UI:
  - `AuthCard`
  - pole e-mail
  - pole hasła
  - link „przypomnij hasło”
  - submit
  - `SocialLoginButtons`
  - link do rejestracji
- Dane / operacje:
  - `auth.login()`
- Endpointy:
  - `POST /api/v1/auth/login`
- Dodatkowe flow:
  - start OAuth przez backendowe `/auth/{provider}/redirect`
  - po sukcesie przejście na `redirect` albo landing

### 7. Rejestracja

- Route: `register`
- URL: `/rejestracja`
- Plik: `resources/js/views/RegisterView.vue`
- Dostęp: publiczny
- Sekcje UI:
  - `AuthCard`
  - nazwa
  - e-mail
  - hasło
  - potwierdzenie hasła
  - `SocialLoginButtons`
  - link do logowania
  - linki do regulaminu i polityki prywatności
- Dane / operacje:
  - `auth.register()`
- Endpointy:
  - `POST /api/v1/auth/register`

### 8. Przypomnienie hasła

- Route: `password.forgot`
- URL: `/przypomnij-haslo`
- Plik: `resources/js/views/ForgotPasswordView.vue`
- Dostęp: publiczny
- Sekcje UI:
  - `AuthCard`
  - e-mail
  - submit
  - komunikat sukcesu
  - komunikat błędu
  - link do logowania
- Operacja:
  - `requestPasswordReset(email)`
- Endpoint:
  - `POST /api/v1/auth/forgot-password`

### 9. Reset hasła

- Route: `password.reset`
- URL: `/reset-hasla`
- Plik: `resources/js/views/ResetPasswordView.vue`
- Dostęp: publiczny
- Parametry wejściowe:
  - query `token`
  - query `email`
- Sekcje UI:
  - `AuthCard`
  - e-mail
  - nowe hasło
  - potwierdzenie
  - status sukcesu
  - status błędu
- Operacja:
  - `resetPassword()`
- Endpoint:
  - `POST /api/v1/auth/reset-password`

### 10. Ekran weryfikacji e-mail

- Route: `email.verify`
- URL: `/weryfikacja-email`
- Plik: `resources/js/views/EmailVerificationView.vue`
- Dostęp: publiczny / zależny od stanu sesji
- Parametry wejściowe:
  - query `status`
- Sekcje UI:
  - `AuthCard`
  - ikona statusu
  - tekst statusu
  - przycisk akcji
- Operacje:
  - `auth.refresh()` po sukcesie
  - `resendVerificationEmail()` dla zalogowanego
- Endpointy:
  - `GET /email/weryfikacja/{id}/{hash}` jako wejście z maila
  - `POST /api/v1/auth/email/verification-notification`

### 11. Regulamin

- Route: `terms`
- URL: `/regulamin`
- Plik: `resources/js/views/TermsView.vue`
- Dostęp: publiczny
- Typ ekranu:
  - statyczny dokument prawny
- Dane z API:
  - brak

### 12. Polityka prywatności

- Route: `privacy`
- URL: `/polityka-prywatnosci`
- Plik: `resources/js/views/PrivacyView.vue`
- Dostęp: publiczny
- Typ ekranu:
  - statyczny dokument prawny
- Dane z API:
  - brak

### 13. 404

- Route: `not-found`
- URL: catch-all
- Plik: `resources/js/views/NotFoundView.vue`
- Dostęp: publiczny
- Typ ekranu:
  - ekran błędu nawigacyjnego
- Sekcje UI:
  - tytuł
  - opis
  - powrót na landing

## Ekrany użytkownika zalogowanego

### 14. Dodawanie ogłoszenia

- Route: `ads.create`
- URL: `/dodaj-ogloszenie`
- Plik: `resources/js/views/AdCreateView.vue`
- Dostęp:
  - zalogowany
  - zweryfikowany e-mail
- Sekcje UI:
  - nagłówek
  - `AdForm`
  - błąd ogólny
- Operacje:
  - `createAd(values)`
- Endpoint:
  - `POST /api/v1/ads`
- Zależności:
  - formularz multipart,
  - obsługa zdjęć,
  - możliwa sugestia kategorii w samym formularzu przez `POST /api/v1/ads/suggest-category`.

### 15. Edycja ogłoszenia

- Route: `ads.edit`
- URL: `/moje-ogloszenia/{slug}/edytuj`
- Plik: `resources/js/views/AdEditView.vue`
- Dostęp:
  - zalogowany
  - zweryfikowany e-mail
- Parametry wejściowe:
  - `params.slug`
- Sekcje UI:
  - spinner
  - błąd ładowania
  - `AdForm`
- Operacje:
  - `fetchAd(slug)`
  - `updateAd(slug, values)`
- Endpointy:
  - `GET /api/v1/ads/{slug}`
  - `POST /api/v1/ads/{slug}`

### 16. Moje ogłoszenia

- Route: `ads.mine`
- URL: `/moje-ogloszenia`
- Plik: `resources/js/views/MyAdsView.vue`
- Dostęp: zalogowany
- Sekcje UI:
  - `DataTable`
  - status
  - cena
  - data publikacji
  - data wygaśnięcia
  - akcje
- Operacje:
  - `fetchMyAds()`
  - `refreshAd(slug)`
  - `deleteAd(slug)`
- Endpointy:
  - `GET /api/v1/my/ads`
  - `POST /api/v1/ads/{slug}/refresh`
  - `DELETE /api/v1/ads/{slug}`

### 17. Ulubione

- Route: `favorites`
- URL: `/ulubione`
- Plik: `resources/js/views/FavoritesView.vue`
- Dostęp: zalogowany
- Sekcje UI:
  - grid kart
  - przycisk ulubionych na karcie
  - paginacja
  - pusty stan
- Operacje:
  - `store.loadFavorites()`
  - pośrednio:
    - `fetchFavorites(page)`
    - `removeFavorite(slug)`
    - `addFavorite(slug)`
- Endpointy:
  - `GET /api/v1/my/favorites`
  - `POST /api/v1/ads/{slug}/favorite`
  - `DELETE /api/v1/ads/{slug}/favorite`

### 18. Lista rozmów

- Route: `messages`
- URL: `/wiadomosci`
- Plik: `resources/js/views/MessagesView.vue`
- Dostęp: zalogowany
- Sekcje UI:
  - lista rozmów
  - avatar/inicjały
  - rozmówca
  - ostatnia wiadomość
  - kontekst ogłoszenia
  - badge nieprzeczytanych
  - przycisk dociągania starszych rozmów
- Operacje:
  - `store.loadConversations()`
  - `store.loadMoreConversations()`
- Endpointy:
  - `GET /api/v1/my/conversations`
  - `GET /api/v1/my/conversations/unread-count`

### 19. Szczegóły rozmowy

- Route: `messages.show`
- URL: `/wiadomosci/{id}`
- Plik: `resources/js/views/ConversationView.vue`
- Dostęp: zalogowany
- Parametry wejściowe:
  - `params.id`
- Sekcje UI:
  - toolbar rozmowy
  - kontekst ogłoszenia
  - `ConversationThread`
  - `ConversationReplyForm`
- Operacje:
  - `store.openConversation(id)`
  - `replyToConversation(id, body)`
- Endpointy:
  - `GET /api/v1/conversations/{id}`
  - `GET /api/v1/conversations/{id}/messages`
  - `POST /api/v1/conversations/{id}/messages`
- Uwagi mobilne:
  - to powinien być ekran typu chat fullscreen,
  - automatyczny scroll do dołu jest istotnym elementem zachowania.

### 20. Profil użytkownika

- Route: `profile`
- URL: `/profil`
- Plik: `resources/js/views/ProfileView.vue`
- Dostęp: zalogowany
- Sekcje UI:
  - hero profilu
  - status konta / weryfikacji
  - avatar
  - `FileUpload`
  - pola:
    - `name`
    - `phone`
    - `bio`
  - zapis profilu
  - sekcja usunięcia konta
- Operacje:
  - `auth.updateProfile()`
  - `auth.deleteAccount()`
- Endpointy:
  - `POST /api/v1/auth/profile`
  - `DELETE /api/v1/auth/account`

## Ekrany administratora

### 21. Kontener panelu admina

- Route: `admin`
- URL: `/admin`
- Plik: `resources/js/views/admin/AdminView.vue`
- Dostęp: administrator
- Sekcje UI:
  - `Tabs`
  - `AdminAdsPanel`
  - `AdminCategoriesPanel`
  - `AdminReportsPanel`
  - `AdminSettingsPanel`

### 22. Admin: ogłoszenia

- Plik: `resources/js/views/admin/AdminAdsPanel.vue`
- Dane:
  - `fetchAdminAds(status, page)`
- Akcje:
  - `approveAd(slug)`
  - `rejectAd(slug, reason)`
  - `deleteAdAsAdmin(slug)`
- Endpointy:
  - `GET /api/v1/admin/ads`
  - `POST /api/v1/admin/ads/{slug}/approve`
  - `POST /api/v1/admin/ads/{slug}/reject`
  - `DELETE /api/v1/admin/ads/{slug}`
- Sekcje UI:
  - filtr statusu
  - tabela
  - pole powodu odrzucenia

### 23. Admin: kategorie

- Plik: `resources/js/views/admin/AdminCategoriesPanel.vue`
- Dane:
  - `fetchAdminCategories()`
- Akcje:
  - `createCategory(payload)`
  - `updateCategory(slug, payload)`
  - `deleteCategory(slug)`
- Endpointy:
  - `GET /api/v1/admin/categories`
  - `POST /api/v1/admin/categories`
  - `PUT /api/v1/admin/categories/{slug}`
  - `DELETE /api/v1/admin/categories/{slug}`
- Sekcje UI:
  - drzewo
  - wyszukiwarka
  - statystyki
  - formularz
  - wybór rodzica
  - widoczność
  - szybkie dodawanie podkategorii

### 24. Admin: zgłoszenia

- Plik: `resources/js/views/admin/AdminReportsPanel.vue`
- Dane:
  - `fetchReports(page)`
- Akcje:
  - `resolveReport(id, status)`
- Endpointy:
  - `GET /api/v1/admin/reports`
  - `PUT /api/v1/admin/reports/{id}`
- Sekcje UI:
  - tabela zgłoszeń
  - powód
  - opis
  - data
  - akcje rozpatrzenia

### 25. Admin: ustawienia

- Plik: `resources/js/views/admin/AdminSettingsPanel.vue`
- Dane:
  - `fetchSettings()`
- Akcje:
  - `updateSettings(autoApprove)`
- Endpointy:
  - `GET /api/v1/admin/settings`
  - `PUT /api/v1/admin/settings`
- Sekcje UI:
  - przełącznik auto-approve,
  - opis działania.

## Ekrany pośrednie i flow niebędące klasycznym ekranem

### OAuth Google / Facebook

- Backend routes:
  - `/auth/google/redirect`
  - `/auth/google/callback`
  - `/auth/facebook/redirect`
  - `/auth/facebook/callback`
- Mobile implication:
  - w appce natywnej najlepiej traktować to jako web auth flow,
  - po powrocie do aplikacji trzeba odświeżyć sesję użytkownika przez `GET /api/v1/auth/me`.

### Wejście z maila weryfikacyjnego

- Backend route:
  - `/email/weryfikacja/{id}/{hash}`
- Mobile implication:
  - po deep linku aplikacja powinna przejść na ekran odpowiadający `email.verify`,
  - potem wykonać refresh użytkownika.

### Globalne elementy nagłówka, które warto odwzorować w mobile

- branding,
- wejście do wyszukiwania,
- motyw jasny/ciemny,
- zmiana języka,
- logowanie / rejestracja lub konto użytkownika,
- ulubione,
- wiadomości,
- panel admina dla roli admin,
- hamburger jako kontener akcji drugorzędnych.

## Rekomendacja mapowania na aplikację mobilną

### Ekrany 1:1 do odwzorowania

- landing,
- listing,
- detal ogłoszenia,
- profil sprzedawcy,
- logowanie,
- rejestracja,
- przypomnienie hasła,
- reset hasła,
- moje ogłoszenia,
- ulubione,
- lista rozmów,
- rozmowa,
- profil użytkownika,
- dodawanie ogłoszenia,
- edycja ogłoszenia.

### Ekrany, które mogą być modalami / webview / screenami pomocniczymi

- regulamin,
- polityka prywatności,
- weryfikacja e-mail,
- 404,
- OAuth callback flow.

### Ekrany, które mogą zostać tylko w panelu webowym

- cały `/admin`, jeśli aplikacja mobilna nie ma obejmować administracji.

## Lista kontrolna kompletności

Opisano wszystkie pliki widoków:

- `LandingView.vue`
- `HomeView.vue`
- `AdDetailView.vue`
- `SellerView.vue`
- `LoginView.vue`
- `RegisterView.vue`
- `ForgotPasswordView.vue`
- `ResetPasswordView.vue`
- `EmailVerificationView.vue`
- `TermsView.vue`
- `PrivacyView.vue`
- `NotFoundView.vue`
- `AdCreateView.vue`
- `AdEditView.vue`
- `MyAdsView.vue`
- `FavoritesView.vue`
- `MessagesView.vue`
- `ConversationView.vue`
- `ProfileView.vue`
- `AdminView.vue`
- `AdminAdsPanel.vue`
- `AdminCategoriesPanel.vue`
- `AdminReportsPanel.vue`
- `AdminSettingsPanel.vue`
