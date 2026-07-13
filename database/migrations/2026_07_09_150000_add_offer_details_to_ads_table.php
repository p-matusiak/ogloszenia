<?php

declare(strict_types=1);

use App\Enums\AdCondition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table): void {
            $table->boolean('is_negotiable')->default(false)->after('price');

            // Stan przedmiotu ma sens tylko dla części kategorii (nie dla ofert
            // pracy czy usług), więc jest opcjonalny.
            $table->string('condition', 12)->nullable()->after('is_negotiable');

            // Lista sposobów wysyłki. jsonb, bo pytamy o „którakolwiek z tych”,
            // a nie o pojedynczą wartość.
            $table->jsonb('delivery_methods')->default(DB::raw("'[]'::jsonb"))->after('condition');
        });

        DB::statement(sprintf(
            "ALTER TABLE ads ADD CONSTRAINT ads_condition_check CHECK (condition IS NULL OR condition IN ('%s'))",
            implode("','", AdCondition::values()),
        ));

        // GIN pod jsonb_exists_any(): filtr „Sposób wysyłki” to jedno trafienie
        // w indeks, a nie skan całej tabeli.
        DB::statement('CREATE INDEX ads_delivery_methods_index ON ads USING GIN (delivery_methods jsonb_path_ops)');

        // „Za darmo” to cena równa zero, nie brak ceny.
        DB::statement('CREATE INDEX ads_free_index ON ads (price) WHERE price = 0');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ads_free_index');
        DB::statement('DROP INDEX IF EXISTS ads_delivery_methods_index');
        DB::statement('ALTER TABLE ads DROP CONSTRAINT IF EXISTS ads_condition_check');

        Schema::table('ads', function (Blueprint $table): void {
            $table->dropColumn(['is_negotiable', 'condition', 'delivery_methods']);
        });
    }
};
