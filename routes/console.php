<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Hourly rather than daily: an ad should leave the public listing close to the
// minute it lapses, and the sweep is a single indexed UPDATE.
Schedule::command('ads:expire')->hourly()->withoutOverlapping();
Schedule::command('ads:warn-deletion')->hourly()->withoutOverlapping();
Schedule::command('ads:purge-expired')->hourly()->withoutOverlapping();
