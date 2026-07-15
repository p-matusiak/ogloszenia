<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdCondition;
use App\Enums\AdStatus;
use App\Services\CategoryClosureRepository;
use App\Support\AdDeletionSchedule;
use App\Support\AdListingPredicate;
use Database\Factories\AdFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * Wyszukiwanie tekstowe działa na podłańcuchach (%fraza%) przez indeks GIN
 * pg_trgm zbudowany na wyrażeniu {@see AdListingPredicate::SEARCH_TEXT_EXPRESSION};
 * nie ma osobnej kolumny wyszukiwania, którą trzeba by chować przed API.
 *
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property string|null $price decimal:2 is cast to string to preserve precision
 * @property-read bool $has_price generated column: price IS NOT NULL
 * @property bool $is_negotiable
 * @property AdCondition|null $condition
 * @property list<string> $delivery_methods
 * @property array<string, string> $delivery_prices
 * @property int $phone_reveals_count
 * @property string|null $location
 * @property string|null $latitude decimal:7 is cast to string to preserve precision
 * @property string|null $longitude decimal:7 is cast to string to preserve precision
 * @property mixed $coordinates PostGIS geography(POINT, 4326); generated, never exposed in API
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property AdStatus $status
 * @property string|null $rejection_reason
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $deletion_warning_sent_at
 * @property Carbon|null $deleted_at
 * @property Carbon $terms_accepted_at
 * @property int $views_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Category $category
 * @property-read Collection<int, AdImage> $images
 * @property-read AdImage|null $primaryImage
 * @property-read Collection<int, AdReport> $reports
 * @property-read Collection<int, AdSlugHistory> $slugHistories
 */
#[Hidden(['has_price'])]
final class Ad extends Model
{
    /** @use HasFactory<AdFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Written exclusively through Actions, never mass-assigned from a request.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<AdImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(AdImage::class)->orderBy('position');
    }

    /**
     * @return HasOne<AdImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(AdImage::class)->where('position', AdImage::PRIMARY_POSITION);
    }

    /**
     * Użytkownicy obserwujący (lubiący) to ogłoszenie.
     *
     * @return BelongsToMany<User, $this>
     */
    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ad_favorites')->withTimestamps();
    }

    /**
     * @return HasMany<AdReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(AdReport::class);
    }

    /**
     * @return HasMany<AdSlugHistory, $this>
     */
    public function slugHistories(): HasMany
    {
        return $this->hasMany(AdSlugHistory::class);
    }

    public function isRefreshable(): bool
    {
        if (! $this->status->isRefreshable() || ! $this->hasLapsed() || $this->expires_at === null) {
            return false;
        }

        return app(AdDeletionSchedule::class)->isWithinRefreshGrace($this->expires_at);
    }

    /**
     * Jedyna definicja „to ogłoszenie widzi każdy”. Sam status nie wystarcza:
     * `ads:expire` chodzi raz na godzinę, więc między wygaśnięciem a przemiataniem
     * ogłoszenie ma jeszcze status `active`. Bez tego warunku wygasłe oferty
     * wchodziłyby do listingu, sitemapy i kanału RSS.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->status->isPubliclyVisible() && ! $this->hasLapsed();
    }

    /**
     * Zniknęło z sieci na dobre — strona takiego ogłoszenia oddaje 410 Gone.
     */
    public function isGone(): bool
    {
        return $this->status->isGone() || $this->hasLapsed();
    }

    public function hasLapsed(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Lustrzane odbicie `isPubliclyVisible()` w SQL. Jedyny dopuszczalny filtr
     * publicznego listingu — każda ścieżka odczytu dla gościa musi przez ten scope.
     * Statyczna część predykatu indeksów listingu: {@see AdListingPredicate}.
     *
     * `expires_at IS NULL` liczy się jako „nie wygasło”, dokładnie tak jak w
     * `ExpireAdsAction`, który tylko takie wiersze pomija.
     *
     * @param  Builder<Ad>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', AdStatus::Active)
            ->where(fn (Builder $lifetime) => $lifetime
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }

    /**
     * Dopasowanie podłańcuchowe: fraza może stać w dowolnym miejscu tytułu, opisu
     * lub lokalizacji (odpowiednik %fraza%), bez oglądania się na granice słów,
     * których pilnował full-text. Każde słowo zapytania musi wystąpić (AND);
     * porównanie jest bezakcentowe i trafia w indeks GIN pg_trgm
     * {@see AdListingPredicate::SEARCH_TEXT_TRGM_INDEX_NAME}.
     *
     * @param  Builder<Ad>  $query
     */
    public function scopeMatching(Builder $query, string $term): void
    {
        foreach ($this->searchWords($term) as $word) {
            $query->whereRaw(
                AdListingPredicate::SEARCH_TEXT_EXPRESSION." LIKE ('%' || f_unaccent(lower(?)) || '%')",
                [$word],
            );
        }
    }

    /**
     * Trafienia w tytule przed tymi, które pasują tylko opisem lub lokalizacją;
     * przy remisie świeższe ogłoszenie wygrywa. Wyrażenie liczone jest na stronie
     * wyników, więc nie potrzebuje własnego indeksu.
     *
     * @param  Builder<Ad>  $query
     */
    public function scopeOrderByRelevance(Builder $query, string $term): void
    {
        $query
            ->orderByRaw(
                "CASE WHEN f_unaccent(lower(title)) LIKE ('%' || f_unaccent(lower(?)) || '%') THEN 0 ELSE 1 END",
                [$this->escapeLikeWildcards(trim($term))],
            )
            ->orderByDesc('published_at');
    }

    /**
     * Słowa zapytania: pozbawione skrajnej interpunkcji i pustych tokenów, ze
     * zneutralizowanymi znakami wieloznacznymi LIKE, by użytkownik nie wstrzyknął
     * własnego wzorca. „rower, & !" → ['rower'].
     *
     * @return list<string>
     */
    private function searchWords(string $term): array
    {
        $tokens = preg_split('/\s+/', trim($term), -1, PREG_SPLIT_NO_EMPTY);
        $words = [];

        foreach ($tokens === false ? [] : $tokens as $token) {
            $trimmed = preg_replace('/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/u', '', $token);

            if ($trimmed !== null && $trimmed !== '') {
                $words[] = $this->escapeLikeWildcards($trimmed);
            }
        }

        return $words;
    }

    private function escapeLikeWildcards(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    /**
     * Ads sitting anywhere in the subtree rooted at the given category slug, so
     * "Motoryzacja" also returns everything filed under "Samochody".
     *
     * @param  Builder<Ad>  $query
     */
    public function scopeInCategoryTree(Builder $query, string $slug): void
    {
        $query->whereIn('category_id', function (QueryBuilder $subquery) use ($slug): void {
            $subquery->select('closure.descendant_id')
                ->from(CategoryClosureRepository::TABLE.' as closure')
                ->join('categories', 'categories.id', '=', 'closure.ancestor_id')
                ->where('categories.slug', $slug);
        });
    }

    /**
     * Ogłoszenia z ustawionymi współrzędnymi w promieniu od punktu. ST_MakePoint
     * przyjmuje (longitude, latitude); ST_DWithin liczy metry na elipsoidzie.
     * Indeks GiST ads_active_coordinates_gist pokrywa ten predykat.
     *
     * @param  Builder<Ad>  $query
     */
    public function scopeWithinRadius(Builder $query, float $latitude, float $longitude, float $radiusKm): void
    {
        $query
            ->whereNotNull('coordinates')
            ->whereRaw(
                'ST_DWithin(coordinates, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$longitude, $latitude, $radiusKm * 1000],
            );
    }

    /**
     * Numer widoczny na ogłoszeniu: nadpisanie z formularza albo telefon z profilu.
     */
    public function resolvedContactPhone(): ?string
    {
        if ($this->contact_phone !== null && $this->contact_phone !== '') {
            return $this->contact_phone;
        }

        if (! $this->relationLoaded('user')) {
            return null;
        }

        return $this->user->phone;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AdStatus::class,
            'condition' => AdCondition::class,
            'delivery_methods' => 'array',
            'delivery_prices' => 'array',
            'phone_reveals_count' => 'integer',
            'is_negotiable' => 'boolean',
            'has_price' => 'boolean',
            'price' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'views_count' => 'integer',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'deletion_warning_sent_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
        ];
    }
}
