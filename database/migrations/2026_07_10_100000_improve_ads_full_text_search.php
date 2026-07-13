<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');

        // Generated columns require an IMMUTABLE expression; the stock unaccent()
        // function is only STABLE, so we wrap it once and reuse everywhere.
        DB::statement("
            CREATE OR REPLACE FUNCTION f_unaccent(text)
            RETURNS text
            LANGUAGE sql
            IMMUTABLE
            PARALLEL SAFE
            STRICT
            RETURN public.unaccent('unaccent', \$1)
        ");

        DB::statement('DROP INDEX IF EXISTS ads_search_vector_index');

        if ($this->searchVectorExists()) {
            DB::statement('ALTER TABLE ads DROP COLUMN search_vector');
        }

        DB::statement("
            ALTER TABLE ads ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                to_tsvector(
                    'simple'::regconfig,
                    f_unaccent(
                        coalesce(title, '') || ' ' ||
                        coalesce(description, '') || ' ' ||
                        coalesce(location, '') || ' ' ||
                        coalesce(district, '')
                    )
                )
            ) STORED
        ");

        DB::statement('CREATE INDEX ads_search_vector_index ON ads USING GIN (search_vector)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_search_vector_index');
        DB::statement('ALTER TABLE ads DROP COLUMN IF EXISTS search_vector');

        DB::statement("
            ALTER TABLE ads ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                to_tsvector('simple'::regconfig, coalesce(title, '') || ' ' || coalesce(description, ''))
            ) STORED
        ");

        DB::statement('CREATE INDEX ads_search_vector_index ON ads USING GIN (search_vector)');

        DB::statement('DROP FUNCTION IF EXISTS f_unaccent(text)');
    }

    private function searchVectorExists(): bool
    {
        $count = DB::selectOne("
            SELECT COUNT(*) AS count
            FROM information_schema.columns
            WHERE table_name = 'ads' AND column_name = 'search_vector'
        ");

        return (int) ($count->count ?? 0) > 0;
    }
};
