# Moduł wyszukiwania aktywnych ogłoszeń

Wyszukiwanie publicznej listy ogłoszeń jest odseparowane za kontraktem, żeby dzisiejszą
implementację relacyjną można było później zastąpić Elasticsearchem/OpenSearchem bez zmian
w kontrolerach, zasobach i testach HTTP. **Dziś nie ma żadnego zewnętrznego silnika** —
jedyną implementacją jest Postgres przez Eloquent.

## Elementy

| Plik | Rola |
|---|---|
| `app/Search/Contracts/AdSearchEngine.php` | Kontrakt granicy: `search(array $criteria): LengthAwarePaginator<Ad>`. |
| `app/Search/Database/DatabaseAdSearchEngine.php` | Jedyna dziś implementacja — filtry, sortowanie, licznik/estymata przez Eloquent. |
| `config/search.php` | Sterownik (`SEARCH_DRIVER`, domyślnie `database`) + mapa `drivers`. |
| `app/Providers/SearchServiceProvider.php` | Binduje kontrakt do implementacji wskazanej przez sterownik. |

Kontroler `Api/V1/AdsController::index` wstrzykuje **kontrakt** `AdSearchEngine`, nigdy konkretną
klasę. Moderacja admina to osobna ścieżka (`AdRepository::paginateForModeration`) — obejmuje
wszystkie statusy, więc nie jest „wyszukiwaniem aktywnych ogłoszeń".

## Dlaczego to jest wymienialne

- **Wejście** to tablica kryteriów (te same klucze co `IndexAdRequest::filters()`), a nie Eloquent
  Builder ani DSL wyszukiwarki. Silnik ES dostanie dokładnie to samo.
- **Wyjście** to paginator modeli `Ad`. Silnik ES odpytałby indeks o pasujące ID i sumę trafień,
  po czym zhydratował modele z bazy w kolejności trafień (wzorzec jak w Laravel Scout) i złożył
  z nich ten sam paginator. Warstwa HTTP (`AdSummaryResource`) niczego nie zauważy.

## Jak dołożyć Elasticsearch (później)

1. Napisz `app/Search/Elasticsearch/ElasticsearchAdSearchEngine implements AdSearchEngine`.
2. Dopisz go do mapy w `config/search.php`:
   `'elasticsearch' => ElasticsearchAdSearchEngine::class`.
3. Ustaw `SEARCH_DRIVER=elasticsearch`.

Żaden kontroler, zasób ani test HTTP się nie zmienia. Ścieżka relacyjna zostaje jako fallback
(`SEARCH_DRIVER=database`).

Testy granicy: `tests/Feature/Search/AdSearchEngineTest.php` — domyślny binding, podmiana przez
sterownik, fallback dla nieznanego sterownika oraz to, że kontroler faktycznie oddaje żądanie
kontraktowi (atrapa `Tests\Fixtures\RecordingAdSearchEngine`).
