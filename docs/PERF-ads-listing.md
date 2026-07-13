# Wydajność listy ogłoszeń — co i dlaczego zmieniono

Data: 2026-07-10. Kontekst: baza zaseedowana do 5 000 004 ogłoszeń i 12 500 004 zdjęć (7,4 GB).

## Diagnoza

Wyszukiwarka nie ma lazy loadingu ani prefetchu. `HomeView.vue` → PrimeVue `Paginator`
→ `changeFilters({ page })` → `watch(filters)` → `adsStore.search()` → `GET /api/v1/ads`
→ `ads.value = page.data` (pełna podmiana, nie append). Lista nie była nigdzie cache'owana.

Wrażenie „pierwsza strona wolniej, kolejne szybko" nie miało pokrycia w bazie. Pomiary
`EXPLAIN ANALYZE` na 5 mln wierszy pokazały, że **każda** strona kosztowała tyle samo,
a rozgrzewanie cache nic nie dawało (sterta 3,8 GB przy `shared_buffers` 128 MB i 2 GB RAM):

| Zapytanie | Przed |
|---|---|
| Strona 1 (`OFFSET 0`) | 2 415 ms |
| Strona 2 (`OFFSET 20`) | 2 448 ms |
| Strona 1000 (`OFFSET 19980`) | 22 444 ms |
| `COUNT(*)` (przebieg zimny / ciepły) | 2 142 ms / 2 176 ms |

Przyczyna: `ORDER BY published_at DESC NULLS LAST` nie pasuje do `ads_status_published_at_index`
(btree ASC NULLS LAST; skan wstecz daje DESC NULLS **FIRST**). Planista schodził na
`Parallel Seq Scan` + `Sort` po 1,58 mln wierszy, żeby oddać 20. Do tego `LengthAwarePaginator`
wołał dokładny `COUNT(*)` przy każdym żądaniu — czyli ~4,5 s na wejście.

## Zmiany

### 1. Sortowanie po dacie — usunięty `NULLS LAST`

`published_at` **jest i musi zostać nullowalny**: `AdPublicationWindow::closed()` zwraca
`published_at => null` dla ogłoszeń `pending` i `rejected`. `NOT NULL` na kolumnie zepsułoby
moderację — to była pierwsza, błędna hipoteza.

Właściwe rozwiązanie: publiczna lista zawsze przechodzi przez `scopePublished()`
(`status = 'active'`), a aktywne ogłoszenie zawsze ma `published_at` (ustawia `open()`).
Ten niezmiennik podniesiono do rangi constraintu:

```sql
ALTER TABLE ads ADD CONSTRAINT ads_active_published_at_present
CHECK (status <> 'active' OR published_at IS NOT NULL) NOT VALID;
ALTER TABLE ads VALIDATE CONSTRAINT ads_active_published_at_present;
```

`NOT VALID` + `VALIDATE` zamiast zwykłego `ADD`: skan idzie pod `SHARE UPDATE EXCLUSIVE`,
bez blokowania odczytów i zapisów.

Dzięki temu `ORDER BY published_at DESC` trafia w `Index Scan Backward`.

### 2. Sortowanie po cenie — kolumna generowana `has_price`

Eloquent nie potrafi wyrazić `NULLS LAST`, a testy wymagają, by ogłoszenia bez ceny lądowały
na końcu w **obu** kierunkach. Predykat zmaterializowano jako kolumnę i sortuje się po dwóch
kluczach (`has_price DESC, price <kierunek>`):

```sql
ALTER TABLE ads ADD COLUMN has_price boolean
  GENERATED ALWAYS AS (price IS NOT NULL) STORED;

CREATE INDEX ads_active_price_asc_index  ON ads (has_price DESC, price ASC)  WHERE status = 'active';
CREATE INDEX ads_active_price_desc_index ON ads (has_price DESC, price DESC) WHERE status = 'active';
```

Uwaga: `ADD COLUMN ... GENERATED ... STORED` przepisuje tabelę (1m 51s na 5 mln wierszy,
`ACCESS EXCLUSIVE`). `DROP COLUMN` jest natychmiastowy.

### 3. `COUNT(*)` — indeks pokrywający + cache

```sql
CREATE INDEX ads_active_expires_at_index ON ads (expires_at) WHERE status = 'active';
```

Indeks pokrywa cały predykat `published()`, więc licznik idzie `Index Only Scan`
(`Heap Fetches: 0`, bufory 493 740 → 4 691). **Wymaga aktualnej mapy widoczności** —
po masowym imporcie trzeba odpalić `VACUUM ANALYZE ads`, inaczej licznik wraca do 2 s.

Dodatkowo `EloquentAdRepository::cachedTotal()` trzyma wynik w Redisie przez
`ads.count_cache_ttl` (domyślnie 60 s, `ADS_COUNT_CACHE_TTL`). Klucz pomija `sort` i `page`,
bo nie zmieniają liczności zbioru — dlatego `?sort=price_desc` trafia w licznik strony głównej.
Inwalidacja wyłącznie przez TTL: licznik jest afordancją UI, nie danymi księgowymi.

### 4. Repository Pattern dla ścieżki odczytu ogłoszeń

- usunięto `app/Services/AdSearchService.php`
- dodano `app/Repositories/Contracts/AdRepository.php` (`paginatePublished`, `paginateForModeration`)
- dodano `app/Repositories/Eloquent/EloquentAdRepository.php`
- dodano `app/Providers/RepositoryServiceProvider.php` + wpis w `bootstrap/providers.php`
- `Api/V1/AdsController` i `Api/V1/Admin/AdsController` zależą od kontraktu
- `AdSort::toSql()` usunięty — enum nie zwraca już SQL-a, kolejność składa repozytorium

### 5. `jsonb_exists_any` → `whereJsonContains`

`whereRaw('jsonb_exists_any(delivery_methods, ?::text[])')` zastąpiono łańcuchem
`orWhereJsonContains`. Generuje `@>`, które trafia w `ads_delivery_methods_index`
(GIN `jsonb_path_ops`) — potwierdzone `Bitmap Index Scan`, 0,186 ms dla selektywnej wartości.

### 6. Filtr po cenie — indeks złożony `(price, expires_at)` (2026-07-13)

Strona z filtrem cenowym ładowała się natychmiast (index-backed `ORDER BY`), ale
`COUNT(*)` dla przedziału schodził na `Seq Scan` po 3,8 GB sterty — 2,5 s na pierwsze
wejście na dany filtr (kolejne z cache 60 s). Sam indeks na `(price)` nie wystarczał:
`Bitmap Heap Scan` i tak sięgał do sterty, żeby sprawdzić `expires_at`.

```sql
CREATE INDEX ads_active_price_expires_index ON ads (price, expires_at) WHERE status = 'active';
```

Obie kolumny w indeksie → `Index Only Scan` (`Heap Fetches: 0`) także dla szerokich zakresów.
Pomaga również stronie dla rzadkich cen (zamiast przewijać `published_at` przez miliony
niepasujących wierszy). Wyniki `COUNT(*)`:

| Zakres | % aktywnych | Przed | Po |
|---|---|---|---|
| `price BETWEEN 1000 AND 1200` | 1,2% | 2 322 ms | 8,6 ms |
| `price BETWEEN 1000 AND 5000` | 24% | 2 579 ms | 121 ms |
| `price >= 1000` | 86% | 2 569 ms | 498 ms |

Endpoint `?price_min=1000` na zimno: 2,8 s → **556 ms**; `?price_min=1000&price_max=5000`: 2,6 s → **84 ms**.

### 7. Szacowany licznik dla filtrów bez indeksu (2026-07-13)

Filtry `delivery`, `condition`, `negotiable`, `location` nie mają pokrywającego indeksu,
więc dokładny `COUNT(*)` seq-skanuje całą stertę — 2,7–3,0 s na pierwsze wejście, niezależnie
od liczby trafień. Strona przy tych filtrach była szybka (2–3 ms z `ads_status_published_at_index`);
wolny był wyłącznie licznik.

`EloquentAdRepository::resolveTotal()` rozdziela dwa przypadki przez `exactCountIsCheap()`:

- **Filtry z pokrywającym indeksem** (kategoria, cena, free, brak filtra) liczy się dokładnie —
  Index Only Scan zwraca prawdę w milisekundach. Dla poddrzewa kategorii to jedyna słuszna droga:
  estymator planisty myli się tu o rzędy wielkości (patrz pkt 8).
- **Filtry bez indeksu** (`SEQ_SCAN_FILTERS` = `q, location, delivery, condition` oraz `negotiable`
  gdy `=== true`) odczytują szacunek z estymatora planisty (`->toBase()->explain()`, parsowany
  `rows=N`, koszt ~2 ms — samo planowanie). Powyżej progu `ads.count_estimate_threshold`
  (domyślnie 10 000) zwracają szacunek, poniżej — dokładny `COUNT(*)`. Dla listy z milionami
  wyników liczba jest tylko poglądowa, a estymata jest bardzo dokładna. Małe zbiory (w tym każdy
  test na `RefreshDatabase`) liczą się dokładnie.

Pułapka, na której łatwo polec: `IndexAdRequest::filters()` zawsze dokłada `negotiable` i `free`
jako boolean, a `filled(false)` w Laravelu to **prawda**. Sprawdzanie samej obecności klucza
uznałoby więc każdy request za „ma filtr bez indeksu" i wszystko liczyłoby szacunkiem. Dlatego
`exactCountIsCheap()` sprawdza realne zastosowanie: `negotiable === true`, reszta `filled()`.

Zmierzona dokładność estymaty na 5 mln: błąd 0,04–0,6%. Czasy zimne (endpoint):

| Filtr | Przed | Po |
|---|---|---|
| `delivery=courier` | 3 026 ms | 199 ms |
| `condition=new` | 2 745 ms | 66 ms |
| `condition=new,used` | — | 89 ms |
| `location=warszawa` | 2 826 ms | 50 ms |

**`q` (full-text) pozostaje wolne z innego powodu** — patrz sekcja „Co zostało".

### 8. Filtr po kategorii — indeks `(category_id, expires_at)` (2026-07-13)

Filtr kategorii rozwija się do `category_id IN (poddrzewo z closure table)`. Strona była szybka
(5,7 ms, semi-join z `ads_status_published_at_index`), ale dokładny `COUNT(*)` robił nested loop
z recheckiem `expires_at` na stercie — **96 664 ms** dla kategorii-korzenia. Estymator planisty tu
nie pomaga: dla semi-join po poddrzewie mylił się o rzędy wielkości (143 783 wobec realnych 819 675,
błąd 82%), więc kategoria **musi** liczyć się dokładnie.

```sql
CREATE INDEX ads_active_category_expires_index ON ads (category_id, expires_at) WHERE status = 'active';
```

Każda podkategoria z listy `IN (...)` liczy się wtedy Index Only Scanem (`Heap Fetches: 0`),
13 pętli × ~3 ms. **96 664 ms → 98 ms**, wynik dokładny (819 675). Endpoint `?category=elektronika`
na zimno: 177 ms.

### 9. Strojenie pamięci Postgresa (2026-07-13)

Kontener LXC dostał ~8 GB RAM (wcześniej 2 GB), ale Postgres wciąż startował z domyślnym
`shared_buffers=128MB` i `work_mem=4MB` — więc tabela `ads` (5,5 GB) się nie cache'owała
(3 przebiegi „laptop": 5,0 → 4,6 → 4,6 s, ciepły cache bezskuteczny), a bitmapy GIN degradowały
do „lossy". `docker-compose.yml` dostał `command:` z parametrami sterowanymi z `.env`:

| Parametr | Przed | Po (env, domyślnie) |
|---|---|---|
| `shared_buffers` | 128 MB | `POSTGRES_SHARED_BUFFERS` = 2 GB |
| `work_mem` | 4 MB | `POSTGRES_WORK_MEM` = 64 MB |
| `maintenance_work_mem` | 64 MB | `POSTGRES_MAINTENANCE_WORK_MEM` = 512 MB |
| `effective_cache_size` | 4 GB | `POSTGRES_EFFECTIVE_CACHE_SIZE` = 6 GB |

Efekt: ciepły cache **zaczął działać**. Patologiczne „laptop" (164 tys. trafień): 4,6 s →
**1,2 s na ciepło** i stabilnie (dane mieszczą się w cache). Reszta 1,2 s to czysty CPU
(rankowanie 164 tys. wierszy) — niżej zejdzie dopiero Meilisearch albo limit kandydatów.
Realne dane (unikalne słowa) były i są sub-milisekundowe. Zmniejsz wartości w `.env`, jeśli
host dzieli pamięć z innymi kontenerami.

### 10. `shm_size` dla Postgresa

Kontener miał domyślne 64 MB `/dev/shm`, przez co równoległy `VACUUM` i `CREATE INDEX`
padały z `could not resize shared memory segment ... No space left on device`.
Dodano `shm_size: ${POSTGRES_SHM_SIZE:-256mb}` w `docker-compose.yml` + wpis w `.env(.example)`.

## Wynik

| Zapytanie | Przed | Po |
|---|---|---|
| Strona 1, sort domyślny | 2 415 ms | 2,2 ms |
| `price_asc` | 2 301 ms | 3,6 ms |
| `price_desc` | 2 289 ms | 1,9 ms |
| `COUNT(*)` | 2 030 ms | 124 ms |

Endpoint `GET /api/v1/ads`: **174 ms na zimno, 30 ms na ciepło** (wcześniej ~4,5 s po stronie bazy).

Quality gate (stan 2026-07-13, po wszystkich punktach): `composer validate --strict` ✓ ·
`php artisan test` ✓ 178 passed (541 assertions) · `vendor/bin/pint --test` ✓ 194 plików ·
`vendor/bin/phpstan analyse --level=6` ✓ bez błędów. Gate frontendowy pominięty — `resources/js` nietknięte.

## Co zostało do zrobienia

**Głęboki `OFFSET` jest nadal liniowy.** Strona 1000 to 1,65 s, strona 100 000 — 156 s.
Naprawa wymaga keyset/cursor pagination, czyli zmiany kontraktu API (`meta.last_page`)
i przebudowy `Paginator`a w `HomeView.vue`.

**`q` (full-text) — wolna jest sama strona, nie licznik.** Sortowanie po trafności liczy
`ts_rank_cd()` dla wszystkich dopasowań i dopiero robi top-N sort: ~4–8 s. Estymata licznika
(pkt 7) tego nie dotyka, bo koszt jest w pobraniu i rankowaniu wierszy, a nie w zliczaniu.

Analiza (2026-07-13), gdyby ktoś wracał do tematu:
- `title` to `varchar(150)`, nie `text` — bez znaczenia, w Postgresie oba to ten sam typ `varlena`.
- Winna jest **kardynalność danych seedera**: 5 mln ogłoszeń ma ~40 różnych tytułów (pula w
  `AdSeederProfile`), każdy powtórzony ~125–164 tys. razy. Więc **każde** słowo trafia w ~164 tys.
  wierszy. Match wymaga rechecku tsvektora na stercie (GIN jest stratny), a to ~1,2 GB I/O.
- **To artefakt seedera, nie problem produkcyjny.** Dowód: unikalne słowo (3 trafienia, jak w
  realnych danych) zwraca pełny wynik z rankowaniem w **0,189 ms** — mechanizm jest szybki.
- Przy tekstowym wyszukiwaniu ma być brany **tylko tytuł, nie opis** (ustalenie z użytkownikiem).
- RUM (jedyny indeks dający ranking prosto z indeksu) **niedostępny** w `postgres:16-alpine`
  (jest tylko `pg_trgm`).
- Więcej RAM pomoże: tabela z indeksami = 5,5 GB, RAM = 2 GB, więc nic się nie cache'uje i każde
  wyszukiwanie czyta z dysku (3 przebiegi „laptop": 5,0 → 4,6 → 4,6 s — cache nie działa). Przy
  ~8 GB tabela zmieści się w page cache: szac. ~0,5–1 s dla patologii, a przede wszystkim
  **odblokowuje Meilisearch** (właściwy fix, instant niezależnie od słowa).

Rekomendacja: podbić RAM kontenera na hoście (`pct set 104 -memory 8192`), potem Laravel Scout +
Meilisearch (osobny PR) — nie dorabiać obejścia w Postgresie pod dane, których w produkcji nie będzie.

**Raw SQL nadal obecny** (czeka na PR z Laravel Scout + Meilisearch):
- `Ad::scopeMatching()` — `search_vector @@ websearch_to_tsquery('simple', f_unaccent(?))`
- `Ad::scopeOrderByRelevance()` — `ts_rank_cd(...)`
- `CategoryClosureRepository` — `DB::table()`
- `Ad::scopeInCategoryTree()` — Query Builder w podzapytaniu

Laravel `whereFullText()` **nie jest zamiennikiem**: postgresowa gramatyka generuje
`to_tsvector('lang', kolumna)` liczone w locie, czyli nie dotknie wygenerowanej kolumny
`search_vector` ani jej indeksu GIN. Przy 5 mln to regres, nie optymalizacja.

**Repository Pattern niekompletny.** Zmigrowano tylko ścieżkę odczytu ogłoszeń.
Pozostałe 26 plików w `app/` odpytuje bazę bezpośrednio. Z tego powodu **nie dodano testu
architektonicznego** — dziś by nie przeszedł.

**Meilisearch wymaga RAM-u.** Kontener LXC ma 2 GB pamięci i wyczerpany swap; indeksowanie
5 mln dokumentów wywoła OOM. Potrzeba ~8 GB przed tym PR-em.
