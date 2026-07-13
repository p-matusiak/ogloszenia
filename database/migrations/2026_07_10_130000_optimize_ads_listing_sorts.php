<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // `published()` zawsze zawęża do status='active', a `open()` ustawia
        // published_at razem ze statusem. Constraint podnosi tę umowę do rangi
        // niezmiennika bazy, dzięki czemu sortowanie może pominąć NULLS LAST
        // i trafić w ads_status_published_at_index zamiast sortować 5 mln wierszy.
        // NOT VALID + VALIDATE zamiast zwykłego ADD: skanowanie idzie pod
        // SHARE UPDATE EXCLUSIVE, bez blokowania odczytów i zapisów.
        DB::statement(<<<'SQL'
            ALTER TABLE ads
            ADD CONSTRAINT ads_active_published_at_present
            CHECK (status <> 'active' OR published_at IS NOT NULL)
            NOT VALID
        SQL);

        DB::statement('ALTER TABLE ads VALIDATE CONSTRAINT ads_active_published_at_present');

        // Cena jest opcjonalna, a ogłoszenia bez ceny muszą lądować na końcu w
        // obu kierunkach sortowania. Eloquent nie potrafi wyrazić NULLS LAST,
        // więc materializujemy predykat jako kolumnę i sortujemy po dwóch
        // kluczach: has_price DESC, price <kierunek>.
        DB::statement(<<<'SQL'
            ALTER TABLE ads
            ADD COLUMN has_price boolean
            GENERATED ALWAYS AS (price IS NOT NULL) STORED
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_price_asc_index
            ON ads (has_price DESC, price ASC)
            WHERE status = 'active'
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_price_desc_index
            ON ads (has_price DESC, price DESC)
            WHERE status = 'active'
        SQL);

        // LengthAwarePaginator liczy COUNT(*) przy każdym żądaniu. Ten indeks
        // pokrywa cały predykat `published()`, więc licznik idzie Index Only
        // Scanem po ~150 MB indeksu zamiast Seq Scanem po 3,8 GB sterty.
        // Index Only Scan wymaga aktualnej mapy widoczności — utrzymuje ją
        // autovacuum, ale po masowym imporcie warto odpalić VACUUM ręcznie.
        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_expires_at_index
            ON ads (expires_at)
            WHERE status = 'active'
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_active_expires_at_index');
        DB::statement('DROP INDEX IF EXISTS ads_active_price_desc_index');
        DB::statement('DROP INDEX IF EXISTS ads_active_price_asc_index');
        DB::statement('ALTER TABLE ads DROP COLUMN IF EXISTS has_price');
        DB::statement('ALTER TABLE ads DROP CONSTRAINT IF EXISTS ads_active_published_at_present');
    }
};
