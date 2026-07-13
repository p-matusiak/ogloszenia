<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Adres, pod którym ogłoszenie było kiedyś dostępne. Bez tej tabeli każda edycja
 * tytułu lub lokalizacji unieważniałaby zaindeksowany URL — `AdSlugGenerator`
 * buduje sluga z obu tych pól.
 *
 * @property int $id
 * @property int $ad_id
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Ad $ad
 */
#[Fillable(['slug'])]
final class AdSlugHistory extends Model
{
    /**
     * @return BelongsTo<Ad, $this>
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
