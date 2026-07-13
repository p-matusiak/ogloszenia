<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Filtr po kategorii rozwija się do category_id IN (poddrzewo z closure).
        // Dokładny COUNT(*) robił nested loop z recheckiem expires_at na stercie —
        // ~1,5 minuty dla kategorii-korzenia. Kolumny (category_id, expires_at)
        // pozwalają liczyć każdą podkategorię Index Only Scanem, bez sterty.
        DB::statement(<<<'SQL'
            CREATE INDEX ads_active_category_expires_index
            ON ads (category_id, expires_at)
            WHERE status = 'active'
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_active_category_expires_index');
    }
};
