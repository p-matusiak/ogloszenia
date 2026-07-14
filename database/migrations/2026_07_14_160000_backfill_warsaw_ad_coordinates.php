<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Uzupełnia współrzędne ogłoszeń z „Warszawa” w polu location.
 * Kolumna `coordinates` (PostGIS) przelicza się sama z latitude/longitude.
 */
return new class extends Migration
{
    /** Środek Warszawy — ten sam punkt co w Nominatim / filtrze geo. */
    private const float WARSAW_LATITUDE = 52.2296756;

    private const float WARSAW_LONGITUDE = 21.0122287;

    public function up(): void
    {
        DB::table('ads')
            ->whereNotNull('location')
            ->whereRaw("f_unaccent(lower(location)) LIKE '%warszawa%'")
            ->update([
                'latitude' => self::WARSAW_LATITUDE,
                'longitude' => self::WARSAW_LONGITUDE,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Migracja danych — przywrócenie poprzednich współrzędnych nie jest możliwe.
    }
};
