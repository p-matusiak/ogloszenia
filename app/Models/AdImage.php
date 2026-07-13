<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AdImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $ad_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property int $size_bytes
 * @property int $position
 * @property-read Ad $ad
 */
#[Fillable(['disk', 'path', 'original_name', 'size_bytes', 'position'])]
final class AdImage extends Model
{
    /** @use HasFactory<AdImageFactory> */
    use HasFactory;

    /**
     * The first image of an ad is its primary image.
     */
    public const int PRIMARY_POSITION = 0;

    /**
     * @return BelongsTo<Ad, $this>
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function isPrimary(): bool
    {
        return $this->position === self::PRIMARY_POSITION;
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Element `<enclosure>` w RSS wymaga typu MIME. Czytamy go z rozszerzenia,
     * bo lista dozwolonych formatów jest zamknięta w `config('ads.images.mimes')`
     * i wymuszona walidacją przy wysyłce.
     */
    public function mimeType(): string
    {
        return match (Str::lower(pathinfo($this->path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'size_bytes' => 'integer',
        ];
    }
}
