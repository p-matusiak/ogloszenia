<?php

declare(strict_types=1);

use App\Support\AdListingPredicate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Usuwa legacy indeksy pełnotabelowe (obejmują soft-deleted) i odtwarza GIN
 * oraz indeks autora wyłącznie na żywych / publicznie listowanych wierszach.
 */
return new class extends Migration
{
    public function up(): void
    {
        $activeOnly = AdListingPredicate::PARTIAL_INDEX_WHERE;
        $notDeleted = AdListingPredicate::SOFT_DELETED_EXCLUDED;

        foreach (AdListingPredicate::LEGACY_INDEX_NAMES as $name) {
            DB::statement("DROP INDEX IF EXISTS {$name}");
        }

        DB::statement('DROP INDEX IF EXISTS '.AdListingPredicate::USER_CREATED_INDEX_NAME);
        DB::statement(<<<SQL
            CREATE INDEX ads_user_id_created_at_index
            ON ads (user_id, created_at DESC)
            WHERE {$notDeleted}
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_delivery_methods_index');
        DB::statement(<<<SQL
            CREATE INDEX ads_delivery_methods_index
            ON ads USING GIN (delivery_methods jsonb_path_ops)
            WHERE {$activeOnly}
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_search_vector_index');
        DB::statement(<<<SQL
            CREATE INDEX ads_search_vector_index
            ON ads USING GIN (search_vector)
            WHERE {$activeOnly}
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_search_vector_index');
        DB::statement('CREATE INDEX ads_search_vector_index ON ads USING GIN (search_vector)');

        DB::statement('DROP INDEX IF EXISTS ads_delivery_methods_index');
        DB::statement('CREATE INDEX ads_delivery_methods_index ON ads USING GIN (delivery_methods jsonb_path_ops)');

        DB::statement('DROP INDEX IF EXISTS '.AdListingPredicate::USER_CREATED_INDEX_NAME);
        DB::statement('CREATE INDEX ads_user_id_created_at_index ON ads (user_id, created_at)');

        DB::statement('CREATE INDEX ads_status_published_at_index ON ads (status, published_at)');
        DB::statement('CREATE INDEX ads_category_id_status_published_at_index ON ads (category_id, status, published_at)');
        DB::statement('CREATE INDEX ads_expires_at_index ON ads (expires_at)');
    }
};
