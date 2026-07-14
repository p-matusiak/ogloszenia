<?php

declare(strict_types=1);

use App\Support\AdListingPredicate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $activeOnly = AdListingPredicate::PARTIAL_INDEX_WHERE;

        DB::statement('DROP INDEX IF EXISTS ads_free_index');
        DB::statement(<<<SQL
            CREATE INDEX ads_free_index
            ON ads (price)
            WHERE price = 0 AND {$activeOnly}
        SQL);

        DB::statement('DROP INDEX IF EXISTS ads_active_published_at_sort_index');
        DB::statement(<<<SQL
            CREATE INDEX ads_active_published_at_sort_index
            ON ads (published_at DESC)
            WHERE {$activeOnly}
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_active_published_at_sort_index');

        DB::statement('DROP INDEX IF EXISTS ads_free_index');
        DB::statement('CREATE INDEX ads_free_index ON ads (price) WHERE price = 0');
    }
};
