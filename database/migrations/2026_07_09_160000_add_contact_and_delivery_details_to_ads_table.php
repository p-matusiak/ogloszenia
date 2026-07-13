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
        Schema::table('ads', function (Blueprint $table): void {
            // Dotychczasowe `location` jest miastem; dzielnica doprecyzowuje je
            // w widoku listy („Warszawa, Mokotów”).
            $table->string('district', 80)->nullable()->after('location');

            // Mapa metoda → cena, np. {"courier": "18.99"}. Brak klucza znaczy
            // „bez podanej ceny”, a nie „za darmo”.
            $table->jsonb('delivery_prices')->default(DB::raw("'{}'::jsonb"))->after('delivery_methods');

            $table->unsignedInteger('phone_reveals_count')->default(0)->after('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table): void {
            $table->dropColumn(['district', 'delivery_prices', 'phone_reveals_count']);
        });
    }
};
