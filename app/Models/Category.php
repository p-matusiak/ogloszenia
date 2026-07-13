<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\CategoryClosureRepository;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A node in the category tree. Ancestor and descendant lookups go through
 * category_closure; parent_id only ever names the immediate parent.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property int $position
 * @property bool $is_visible
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category|null $parent
 * @property-read Collection<int, Category> $children
 * @property-read Collection<int, Category> $ancestors
 * @property-read Collection<int, Category> $descendants
 * @property-read Collection<int, CategorySlugHistory> $slugHistories
 */
#[Fillable(['parent_id', 'name', 'slug', 'position', 'is_visible'])]
final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    /**
     * Strict ancestors, nearest first. Excludes the zero-depth self row.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function ancestors(): BelongsToMany
    {
        return $this->belongsToMany(self::class, CategoryClosureRepository::TABLE, 'descendant_id', 'ancestor_id')
            ->withPivot('depth')
            ->wherePivot('depth', '>', 0)
            ->orderByPivot('depth');
    }

    /**
     * Strict descendants: the whole subtree below this node.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function descendants(): BelongsToMany
    {
        return $this->belongsToMany(self::class, CategoryClosureRepository::TABLE, 'ancestor_id', 'descendant_id')
            ->withPivot('depth')
            ->wherePivot('depth', '>', 0)
            ->orderByPivot('depth');
    }

    /**
     * @return HasMany<Ad, $this>
     */
    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }

    /**
     * @return HasMany<CategorySlugHistory, $this>
     */
    public function slugHistories(): HasMany
    {
        return $this->hasMany(CategorySlugHistory::class);
    }

    /**
     * @param  Builder<Category>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * @param  Builder<Category>  $query
     */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'position' => 'integer',
        ];
    }
}
