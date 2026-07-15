<?php

declare(strict_types=1);

use App\Support\AdListingPredicate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Wyszukiwarka ma dopasowywać frazę w środku wyrazu (%fraza%), a nie tylko całe
 * słowa. Full-text (tsvector + websearch_to_tsquery) łapał wyłącznie kompletne
 * tokeny, więc „ower" nie trafiało w „rower". Zastępujemy go dopasowaniem
 * podłańcuchowym ILIKE wspieranym przez indeks GIN pg_trgm, zbudowany na tym
 * samym znormalizowanym wyrażeniu, którego używa Ad::scopeMatching().
 */
return new class extends Migration
{
    public function up(): void
    {
        $activeOnly = AdListingPredicate::PARTIAL_INDEX_WHERE;
        $expression = AdListingPredicate::SEARCH_TEXT_EXPRESSION;
        $index = AdListingPredicate::SEARCH_TEXT_TRGM_INDEX_NAME;

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        DB::statement('DROP INDEX IF EXISTS ads_search_vector_index');
        DB::statement('ALTER TABLE ads DROP COLUMN IF EXISTS search_vector');

        DB::statement(<<<SQL
            CREATE INDEX {$index}
            ON ads USING GIN (({$expression}) gin_trgm_ops)
            WHERE {$activeOnly}
        SQL);
    }

    public function down(): void
    {
        $activeOnly = AdListingPredicate::PARTIAL_INDEX_WHERE;

        DB::statement('DROP INDEX IF EXISTS '.AdListingPredicate::SEARCH_TEXT_TRGM_INDEX_NAME);

        DB::statement(<<<'SQL'
            ALTER TABLE ads ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                to_tsvector(
                    'simple'::regconfig,
                    f_unaccent(
                        coalesce(title, '') || ' ' ||
                        coalesce(description, '') || ' ' ||
                        coalesce(location, '')
                    )
                )
            ) STORED
        SQL);

        DB::statement(<<<SQL
            CREATE INDEX ads_search_vector_index
            ON ads USING GIN (search_vector)
            WHERE {$activeOnly}
        SQL);
    }
};
