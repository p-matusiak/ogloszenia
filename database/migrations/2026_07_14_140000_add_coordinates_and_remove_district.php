<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_search_vector_index');
        DB::statement('ALTER TABLE ads DROP COLUMN IF EXISTS search_vector');

        Schema::table('ads', function (Blueprint $table): void {
            $table->dropColumn('district');
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('default_location', 120)->nullable()->after('phone');
            $table->decimal('default_latitude', 10, 7)->nullable()->after('default_location');
            $table->decimal('default_longitude', 10, 7)->nullable()->after('default_latitude');
        });

        DB::statement("
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
        ");

        DB::statement('CREATE INDEX ads_search_vector_index ON ads USING GIN (search_vector)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_search_vector_index');
        DB::statement('ALTER TABLE ads DROP COLUMN IF EXISTS search_vector');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['default_location', 'default_latitude', 'default_longitude']);
        });

        Schema::table('ads', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude']);
            $table->string('district', 80)->nullable()->after('location');
        });

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
};
