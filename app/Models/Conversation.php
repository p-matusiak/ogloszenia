<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ad_id
 * @property int $buyer_id
 * @property int $seller_id
 * @property int|null $last_sender_id
 * @property string|null $last_message_preview
 * @property Carbon|null $last_message_at
 * @property Carbon|null $buyer_last_read_at
 * @property Carbon|null $seller_last_read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Ad $ad
 * @property-read User $buyer
 * @property-read User $seller
 * @property-read User|null $lastSender
 */
final class Conversation extends Model
{
    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<Ad, $this>
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lastSender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_sender_id');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @param  Builder<Conversation>  $query
     * @return Builder<Conversation>
     */
    public function scopeForParticipant(Builder $query, User $user): Builder
    {
        return $query->where(
            fn (Builder $inner): Builder => $inner
                ->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id),
        );
    }

    public function involves(User $user): bool
    {
        return $this->buyer_id === $user->id || $this->seller_id === $user->id;
    }

    public function otherParty(User $user): User
    {
        if ($this->buyer_id === $user->id) {
            return $this->seller;
        }

        return $this->buyer;
    }

    public function isUnreadFor(User $user): bool
    {
        if ($this->last_message_at === null || $this->last_sender_id === null) {
            return false;
        }

        if ($this->last_sender_id === $user->id) {
            return false;
        }

        $lastReadAt = $this->buyer_id === $user->id
            ? $this->buyer_last_read_at
            : $this->seller_last_read_at;

        return $lastReadAt === null || $this->last_message_at->isAfter($lastReadAt);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'buyer_last_read_at' => 'datetime',
            'seller_last_read_at' => 'datetime',
        ];
    }
}
