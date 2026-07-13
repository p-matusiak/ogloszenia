<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Filtr po cenie: strona ładuje się z ads_status_published_at_index, ale
        // COUNT(*) dla przedziału cenowego schodził na Seq Scan po 3,8 GB sterty
        // (2,5 s), bo żaden indeks nie pokrywał predykatu price + expires_at.
        // Kolumny w kolejności (price, expires_at) pozwalają liczyć przedział
        // Index Only Scanem — bez sięgania do sterty także dla szerokich zakresów.
        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_price_expires_index
            ON ads (price, expires_at)
            WHERE status = 'active'
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_active_price_expires_index');
    }
};
