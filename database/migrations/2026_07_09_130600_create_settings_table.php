<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->string('key', 64)->primary();

            // jsonb keeps the column typed for booleans today and structured
            // values later, without a migration per setting.
            $table->jsonb('value');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
