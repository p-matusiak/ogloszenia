<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Jeden użytkownik obserwuje dane ogłoszenie najwyżej raz.
            $table->unique(['user_id', 'ad_id']);
            // Lista ulubionych użytkownika, najnowsze pierwsze.
            $table->index(['user_id', 'created_at']);
            // Fan-out powiadomień: kto obserwuje to ogłoszenie.
            $table->index('ad_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_favorites');
    }
};
