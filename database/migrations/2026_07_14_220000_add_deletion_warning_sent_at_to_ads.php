<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table): void {
            $table->timestampTz('deletion_warning_sent_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table): void {
            $table->dropColumn('deletion_warning_sent_at');
        });
    }
};
