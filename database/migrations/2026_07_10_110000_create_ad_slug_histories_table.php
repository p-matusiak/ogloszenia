<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_slug_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ad_id')->constrained()->cascadeOnDelete();

            // Unikalny w obrębie całej tabeli, a `AdSlugGenerator` sprawdza tu
            // kolizje razem z `ads.slug`: adres raz opublikowany nigdy nie może
            // zacząć wskazywać na inne ogłoszenie.
            $table->string('slug', 200)->unique();

            $table->timestampsTz();

            $table->index('ad_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_slug_histories');
    }
};
