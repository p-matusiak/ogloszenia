<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailAddress;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $email
 * @property string $password
 * @property string|null $avatar_path
 * @property string|null $bio
 * @property string|null $phone
 * @property string|null $default_location
 * @property string|null $default_latitude decimal:7 is cast to string to preserve precision
 * @property string|null $default_longitude decimal:7 is cast to string to preserve precision
 * @property bool $is_admin
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property-read Collection<int, Ad> $ads
 * @property-read Collection<int, Ad> $favoriteAds
 * @property-read Collection<int, UserSlugHistory> $slugHistories
 */
#[Fillable(['name', 'email', 'password', 'avatar_path', 'bio', 'phone', 'default_location', 'default_latitude', 'default_longitude'])]
#[Hidden(['password', 'remember_token'])]
final class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmailTrait, Notifiable, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Ad, $this>
     */
    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }

    /**
     * Aktywne, niewygasłe ogłoszenia — licznik na profilu sprzedawcy.
     *
     * @return HasMany<Ad, $this>
     */
    public function activeAds(): HasMany
    {
        return $this->hasMany(Ad::class)->published();
    }

    /**
     * Ogłoszenia obserwowane (ulubione) przez użytkownika.
     *
     * @return BelongsToMany<Ad, $this>
     */
    public function favoriteAds(): BelongsToMany
    {
        return $this->belongsToMany(Ad::class, 'ad_favorites')->withTimestamps();
    }

    /**
     * @return HasMany<UserSlugHistory, $this>
     */
    public function slugHistories(): HasMany
    {
        return $this->hasMany(UserSlugHistory::class);
    }

    /**
     * Overrides the trait so the Polish, queued notification is used instead of
     * Laravel's built-in English one.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailAddress);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification((string) $token));
    }

    public function avatarUrl(): ?string
    {
        if ($this->avatar_path === null) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'default_latitude' => 'decimal:7',
            'default_longitude' => 'decimal:7',
        ];
    }
}
