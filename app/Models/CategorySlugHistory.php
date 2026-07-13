<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Adres, pod którym strona kategorii była kiedyś dostępna. Zmiana nazwy kategorii
 * przebudowuje sluga, a strona kategorii jest adresem, na który linkuje sitemapa
 * i który zbiera pozycję w wyszukiwarce — nie może zniknąć po edycji w panelu.
 *
 * @property int $id
 * @property int $category_id
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category $category
 */
#[Fillable(['slug'])]
final class CategorySlugHistory extends Model
{
    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
