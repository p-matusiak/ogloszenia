<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Ad;

/**
 * Jedno źródło prawdy dla publicznego listingu: scope {@see Ad::scopePublished()}
 * oraz predykat indeksów częściowych pod aktywne, niesoft-usunięte ogłoszenia.
 *
 * Warunek expires_at jest zależny od czasu i nie może wejść do partial index — filtruje
 * się przy skanie indeksu albo na stercie.
 */
final class AdListingPredicate
{
    /** Predykat „wiersz nie jest soft-usunięty” — indeksy UNIQUE i listingu. */
    public const string SOFT_DELETED_EXCLUDED = 'deleted_at IS NULL';

    public const string PARTIAL_INDEX_WHERE = "status = 'active' AND ".self::SOFT_DELETED_EXCLUDED;

    /**
     * Znormalizowany tekst przeszukiwany przez {@see Ad::scopeMatching()}: tytuł,
     * opis i lokalizacja złączone, złożone małymi literami i pozbawione akcentów.
     * Ta sama, dosłownie identyczna definicja karmi indeks GIN pg_trgm
     * {@see self::SEARCH_TEXT_TRGM_INDEX_NAME} — planer użyje indeksu tylko wtedy,
     * gdy wyrażenie w predykacie i w indeksie są znak w znak takie same.
     */
    public const string SEARCH_TEXT_EXPRESSION =
        "f_unaccent(lower(coalesce(title, '') || ' ' || coalesce(description, '') || ' ' || coalesce(location, '')))";

    public const string SEARCH_TEXT_TRGM_INDEX_NAME = 'ads_search_text_trgm_index';

    /**
     * Slug jest unikalny tylko wśród żywych wierszy; soft delete zwalnia adres URL.
     *
     * @var list<string>
     */
    public const array PARTIAL_UNIQUE_INDEX_NAMES = [
        'ads_slug_unique',
        'users_slug_unique',
    ];

    /**
     * Indeksy częściowe pokrywające statyczną część published() dla listingu.
     *
     * @var list<string>
     */
    public const array PARTIAL_INDEX_NAMES = [
        'ads_active_category_expires_index',
        'ads_active_coordinates_gist',
        'ads_active_expires_at_index',
        'ads_active_price_asc_index',
        'ads_active_price_desc_index',
        'ads_active_price_expires_index',
        'ads_active_published_at_sort_index',
        'ads_free_index',
        'ads_user_active_published_at_index',
    ];

    /**
     * GIN pod publiczne wyszukiwanie i filtry oferty — tylko aktywne, żywe wiersze.
     *
     * @var list<string>
     */
    public const array PARTIAL_GIN_INDEX_NAMES = [
        'ads_delivery_methods_index',
        self::SEARCH_TEXT_TRGM_INDEX_NAME,
    ];

    /**
     * Panel autora: wszystkie statusy oprócz soft delete.
     */
    public const string USER_CREATED_INDEX_NAME = 'ads_user_id_created_at_index';

    /**
     * Zastąpione indeksami częściowymi {@see PARTIAL_INDEX_NAMES} — tylko do DROP.
     *
     * @var list<string>
     */
    public const array LEGACY_INDEX_NAMES = [
        'ads_category_id_status_published_at_index',
        'ads_expires_at_index',
        'ads_status_published_at_index',
    ];
}
