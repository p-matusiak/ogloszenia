<?php

declare(strict_types=1);

use App\Support\AdListingPredicate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Soft-deleted wiersze muszą być niewidoczne także dla UNIQUE — inaczej slug
 * „zajęty” zostaje na zawsze, a masowy soft delete w seederze wygląda jak błąd.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ads DROP CONSTRAINT IF EXISTS ads_slug_unique');
        DB::statement('DROP INDEX IF EXISTS ads_slug_unique');

        DB::statement(sprintf(
            'CREATE UNIQUE INDEX ads_slug_unique ON ads (slug) WHERE %s',
            AdListingPredicate::SOFT_DELETED_EXCLUDED,
        ));

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_slug_unique');
        DB::statement('DROP INDEX IF EXISTS users_slug_unique');

        DB::statement(sprintf(
            'CREATE UNIQUE INDEX users_slug_unique ON users (slug) WHERE %s',
            AdListingPredicate::SOFT_DELETED_EXCLUDED,
        ));
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_slug_unique');
        DB::statement('CREATE UNIQUE INDEX users_slug_unique ON users (slug)');

        DB::statement('DROP INDEX IF EXISTS ads_slug_unique');
        DB::statement('ALTER TABLE ads ADD CONSTRAINT ads_slug_unique UNIQUE (slug)');
    }
};
