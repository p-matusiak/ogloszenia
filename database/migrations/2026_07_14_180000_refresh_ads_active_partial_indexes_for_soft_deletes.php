<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * SoftDeletes dokłada deleted_at IS NULL do każdego zapytania. Indeksy
     * częściowe ze samym status = 'active' tracą Index Only Scan — planista
     * schodzi na Seq Scan po 5 mln wierszy (~800 ms na COUNT). Predykat musi
     * pokrywać oba warunki published() i soft delete.
     */
    public function up(): void
    {
        $activeOnly = "status = 'active' AND deleted_at IS NULL";

        DB::statement('DROP INDEX IF EXISTS ads_active_price_asc_index');
        DB::statement(<<<SQL
            CREATE INDEX ads_active_price_asc_index
            ON ads (has_price DESC, price ASC)
            WHERE {$activeOnly}
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_active_price_desc_index');
        DB::statement(<<<SQL
            CREATE INDEX ads_active_price_desc_index
            ON ads (has_price DESC, price DESC)
            WHERE {$activeOnly}
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_active_expires_at_index');
        DB::statement(<<<SQL
            CREATE INDEX ads_active_expires_at_index
            ON ads (expires_at)
            WHERE {$activeOnly}
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_active_price_expires_index');
        DB::statement(<<<SQL
            CREATE INDEX ads_active_price_expires_index
            ON ads (price, expires_at)
            WHERE {$activeOnly}
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_active_category_expires_index');
        DB::statement(<<<SQL
            CREATE INDEX ads_active_category_expires_index
            ON ads (category_id, expires_at)
            WHERE {$activeOnly}
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_user_active_published_at_index');
        DB::statement(<<<SQL
            CREATE INDEX ads_user_active_published_at_index
            ON ads (user_id, published_at DESC)
            WHERE {$activeOnly}
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_active_coordinates_gist');
        DB::statement(<<<SQL
            CREATE INDEX ads_active_coordinates_gist
            ON ads USING GIST (coordinates)
            WHERE {$activeOnly} AND coordinates IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_active_coordinates_gist');
        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_coordinates_gist
            ON ads USING GIST (coordinates)
            WHERE status = 'active' AND coordinates IS NOT NULL
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_user_active_published_at_index');
        DB::statement(<<<'SQL'
            CREATE INDEX ads_user_active_published_at_index
            ON ads (user_id, published_at DESC)
            WHERE status = 'active'
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_active_category_expires_index');
        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_category_expires_index
            ON ads (category_id, expires_at)
            WHERE status = 'active'
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_active_price_expires_index');
        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_price_expires_index
            ON ads (price, expires_at)
            WHERE status = 'active'
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_active_expires_at_index');
        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_expires_at_index
            ON ads (expires_at)
            WHERE status = 'active'
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_active_price_desc_index');
        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_price_desc_index
            ON ads (has_price DESC, price DESC)
            WHERE status = 'active'
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_active_price_asc_index');
        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_price_asc_index
            ON ads (has_price DESC, price ASC)
            WHERE status = 'active'
        SQL);
    }
};
