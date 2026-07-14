<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Zapytanie „inne ogłoszenia sprzedawcy” filtruje po user_id i sortuje po
        // published_at. Bez tego indeksu planista bierze ads_status_published_at_index
        // i skanuje setki tysięcy aktywnych wierszy, dopóki nie znajdzie LIMIT 4.
        DB::statement(<<<'SQL'
            CREATE INDEX ads_user_active_published_at_index
            ON ads (user_id, published_at DESC)
            WHERE status = 'active'
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_user_active_published_at_index');
    }
};
