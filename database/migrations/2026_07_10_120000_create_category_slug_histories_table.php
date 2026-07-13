<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_slug_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            // Unikalny globalnie, tak jak `categories.slug`, i sprawdzany przez
            // `CategorySlugGenerator`: adres strony kategorii raz opublikowany
            // nigdy nie może zacząć wskazywać na inną gałąź drzewa.
            $table->string('slug', 120)->unique();

            $table->timestampsTz();

            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_slug_histories');
    }
};
