# Ulubione ogłoszenia + powiadomienia o zmianie

Użytkownik może obserwować (polubić) **aktywne** ogłoszenie. Gdy obserwowane ogłoszenie zostanie
zmienione w istotnym polu (tytuł, opis, cena), obserwujący dostaje e-mail. Nieaktywne ogłoszenia
znikają z ulubionych.

## „Znika z ulubionych" = filtrowanie przy zapytaniu

Wpis w `ad_favorites` **nie jest kasowany**, gdy ogłoszenie traci aktywność. Lista i licznik
ulubionych przechodzą przez `scopePublished()`, więc pokazują wyłącznie aktywne. To odporne na
każdą drogę utraty aktywności (wygaśnięcie, odrzucenie, usunięcie) bez podpinania się pod każdą
z nich osobno. `activeFavoriteIdsFor()` też zwraca tylko aktywne.

## Backend

| Warstwa | Plik |
|---|---|
| Migracja | `ad_favorites` (user_id, ad_id, unique, indeksy `(user_id,created_at)` i `ad_id`, FK cascade) |
| Relacje | `User::favoriteAds()`, `Ad::favoritedByUsers()` (belongsToMany) |
| Repozytorium | `FavoriteRepository` → `EloquentFavoriteRepository` (binding w `RepositoryServiceProvider`) |
| Akcje | `AddFavoriteAction` (odrzuca nieaktywne przez `AdNotFavoritableException`), `RemoveFavoriteAction` |
| Powiadomienie | `UpdateAdAction` wykrywa zmianę pól `[title, description, price]` → `event(AdWasUpdated)` po commicie → listener `NotifyFavoritersOfAdUpdate` (kolejkowany) → `FavoritedAdChanged` (mail, kolejkowany) |
| HTTP | `FavoritesController`: `POST/DELETE /ads/{ad}/favorite`, `GET /my/favorites`, `GET /my/favorites/ids` |

Powiadomienie wysyłane tylko gdy ogłoszenie jest aktywne i zmieniło się istotne pole; obserwujący
nieaktywnych ogłoszeń i tak nie ma (nie widać ich na liście).

## Frontend

- `stores/favorites.ts` — zbiór id (ładowany raz), lista, `toggle`, `loadFavorites`, `reset`.
- `api/modules/v1/favorites.ts` — cztery wywołania.
- `components/ads/FavoriteButton.vue` — serduszko (toggle), tylko dla zalogowanych.
- `views/FavoritesView.vue` + trasa `/ulubione` (`requiresAuth`) + link w `AppHeader`.
- Serduszko na **detalu ogłoszenia** (tylko aktywne) i na **stronie ulubionych**. Karty listy
  (`AdCard`) świadomie nietknięte — ich test montuje bez Pinii, a stan serduszek i tak trzyma store
  (endpoint `/my/favorites/ids`).

## Audyt dostępu do bazy (reguła Repository Pattern)

Nowa funkcja: **wszystkie** zapytania w `EloquentFavoriteRepository` (przez relacje Eloquent, bez
raw SQL / `DB::table()`). Akcje i listener zależą od kontraktu `FavoriteRepository`.

Istniejący kod pozostaje pragmatycznym hybrydem (np. `MyAdsController` i `UpdateAdAction` operują
na Eloquent bezpośrednio) — zgodnie z decyzjami z wcześniejszych sesji nie refaktoryzowano całej
aplikacji ani nie dodano globalnego testu architektonicznego, który by na tym istniejącym kodzie
nie przeszedł.

## Quality gate (2026-07-13)

Backend: `composer validate` ✓ · `php artisan test` ✓ 197 · `pint --test` ✓ · `phpstan lvl 6` ✓.
Frontend: `typecheck` ✓ · `test:unit` ✓ 151 · `lint` ✓ · `build` ✓.
