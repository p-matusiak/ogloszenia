<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ad_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 32);
            $table->string('path');
            $table->string('original_name');
            $table->unsignedInteger('size_bytes');
            // Position 0 is the primary image. The unique constraint below
            // therefore also guarantees at most one primary image per ad.
            $table->unsignedSmallInteger('position');
            $table->timestampsTz();

            $table->unique(['ad_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_images');
    }
};
