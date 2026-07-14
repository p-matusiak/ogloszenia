<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        DB::statement(<<<'SQL'
            ALTER TABLE ads
            ADD COLUMN coordinates geography(POINT, 4326)
            GENERATED ALWAYS AS (
                CASE
                    WHEN latitude IS NOT NULL AND longitude IS NOT NULL
                    THEN ST_SetSRID(
                        ST_MakePoint(longitude::double precision, latitude::double precision),
                        4326
                    )::geography
                    ELSE NULL
                END
            ) STORED
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_coordinates_gist
            ON ads USING GIST (coordinates)
            WHERE status = 'active' AND coordinates IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_active_coordinates_gist');
        DB::statement('ALTER TABLE ads DROP COLUMN IF EXISTS coordinates');
    }
};
