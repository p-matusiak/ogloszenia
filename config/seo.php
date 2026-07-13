<?php

declare(strict_types=1);

return [
    'site_name' => env('APP_NAME', 'Ogłoszenia'),

    'default_description' => 'Serwis ogłoszeń drobnych — dodawaj, przeglądaj i wyszukuj ogłoszenia.',

    /*
     * Sitemapa i kanały RSS są identyczne dla każdego odwiedzającego, a każda
     * budowa to przejście po całej tabeli ogłoszeń. Invalidation jest wyłącznie
     * czasowa i to jest świadoma decyzja: kanał spóźniony o godzinę nikomu nie
     * szkodzi, a przebudowa przy każdej publikacji ogłoszenia byłaby czystym
     * marnotrawstwem CPU przy zerowym zysku dla robota.
     */
    'cache_ttl' => (int) env('SEO_CACHE_TTL', 3600),

    'feed' => [
        /*
         * Czytniki RSS i tak trzymają tylko ostatnie pozycje, a kanał musi się
         * mieścić w jednym żądaniu HTTP.
         */
        'limit' => (int) env('SEO_FEED_LIMIT', 50),
    ],

    'sitemap' => [
        /*
         * Protokół sitemap dopuszcza 50 000 URL-i w jednym pliku. Zostawiamy
         * zapas na kategorie i strony statyczne; po przekroczeniu tego progu
         * trzeba przejść na sitemap index.
         */
        'max_ads' => (int) env('SEO_SITEMAP_MAX_ADS', 45_000),
    ],

    /*
     * Jedyny parametr, który współtworzy tożsamość strony listingu. Kategoria ma
     * własny adres (`/kategoria/{slug}`), a reszta filtrów (`q`, cena, stan,
     * dostawa, sortowanie) pokazuje tę samą treść w innej kolejności — canonical
     * sprowadza każdą taką kombinację do adresu bez nich. Bez tego parametry
     * z `useRouteFilters.ts` dają robotowi praktycznie nieskończoną liczbę
     * adresów o identycznej zawartości.
     */
    'canonical_query_params' => ['page'],

    'robots' => [
        'disallow' => [
            '/admin',
            '/moje-ogloszenia',
            '/profil',
            '/dodaj-ogloszenie',
            '/logowanie',
            '/rejestracja',
            '/weryfikacja-email',
            // Wyniki wyszukiwania i przesortowane listingi to treść wtórna:
            // niech robot zużyje budżet na ogłoszenia i kategorie.
            '/*?*q=',
            '/*?*sort=',
        ],
    ],

    /*
     * Trasy powłoki SPA renderowane przez SpaController. Klucz jest nazwą trasy
     * Laravela i musi odpowiadać nazwie w `resources/js/router/index.ts`, bo obie
     * strony opisują ten sam adres. Lista jest wyliczona zamiast złapana
     * catch-allem — dzięki temu nieistniejący adres kończy się realnym 404.
     */
    'pages' => [
        'home' => [
            'path' => '/',
            'title' => null,
            'description' => 'Tysiące ogłoszeń drobnych w jednym miejscu. Kupuj i sprzedawaj lokalnie — motoryzacja, elektronika, dom i ogród.',
            'indexable' => true,
        ],
        'terms' => [
            'path' => 'regulamin',
            'title' => 'Regulamin serwisu',
            'description' => 'Zasady korzystania z serwisu ogłoszeń drobnych.',
            'indexable' => true,
        ],
        'privacy' => [
            'path' => 'polityka-prywatnosci',
            'title' => 'Polityka prywatności',
            'description' => 'Jak przetwarzamy dane osobowe użytkowników serwisu ogłoszeń.',
            'indexable' => true,
        ],
        'login' => [
            'path' => 'logowanie',
            'title' => 'Logowanie',
            'description' => 'Zaloguj się, aby zarządzać swoimi ogłoszeniami.',
            'indexable' => false,
        ],
        'register' => [
            'path' => 'rejestracja',
            'title' => 'Rejestracja',
            'description' => 'Załóż bezpłatne konto i publikuj ogłoszenia.',
            'indexable' => false,
        ],
        'email.verify' => [
            'path' => 'weryfikacja-email',
            'title' => 'Weryfikacja adresu e-mail',
            'description' => 'Potwierdź adres e-mail, aby publikować ogłoszenia.',
            'indexable' => false,
        ],
        'ads.create' => [
            'path' => 'dodaj-ogloszenie',
            'title' => 'Dodaj ogłoszenie',
            'description' => 'Wystaw ogłoszenie bezpłatnie w kilka minut.',
            'indexable' => false,
        ],
        'ads.mine' => [
            'path' => 'moje-ogloszenia',
            'title' => 'Moje ogłoszenia',
            'description' => 'Zarządzaj swoimi ogłoszeniami.',
            'indexable' => false,
        ],
        'ads.edit' => [
            'path' => 'moje-ogloszenia/{slug}/edytuj',
            'title' => 'Edycja ogłoszenia',
            'description' => 'Edytuj treść swojego ogłoszenia.',
            'indexable' => false,
        ],
        'profile' => [
            'path' => 'profil',
            'title' => 'Profil',
            'description' => 'Ustawienia konta.',
            'indexable' => false,
        ],
        'admin' => [
            'path' => 'admin',
            'title' => 'Panel administratora',
            'description' => 'Moderacja ogłoszeń i kategorii.',
            'indexable' => false,
        ],
    ],
];
