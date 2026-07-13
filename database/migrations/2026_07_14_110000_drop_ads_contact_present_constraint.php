<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ads DROP CONSTRAINT IF EXISTS ads_contact_present');
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE ads ADD CONSTRAINT ads_contact_present CHECK (contact_email IS NOT NULL OR contact_phone IS NOT NULL)',
        );
    }
};
