<?php

declare(strict_types=1);

namespace App\Models;

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
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $avatar_path
 * @property string|null $bio
 * @property string|null $phone
 * @property bool $is_admin
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $created_at
 * @property-read Collection<int, Ad> $ads
 * @property-read Collection<int, Ad> $favoriteAds
 */
#[Fillable(['name', 'email', 'password', 'avatar_path', 'bio', 'phone'])]
#[Hidden(['password', 'remember_token'])]
final class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmailTrait, Notifiable;

    /**
     * @return HasMany<Ad, $this>
     */
    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
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
     * Overrides the trait so the Polish, queued notification is used instead of
     * Laravel's built-in English one.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailAddress);
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
        ];
    }
}
