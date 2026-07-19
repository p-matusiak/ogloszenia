<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class TemporaryAdImageStorage
{
    private const string CACHE_PREFIX = 'ads:temp-image:';

    /**
     * @return array{token: string, preview_url: string, original_name: string, size_bytes: int}
     */
    public function storeForUser(User $user, UploadedFile $image): array
    {
        $token = (string) Str::uuid();
        $disk = (string) Config::get('ads.images.disk', 'public');
        $prefix = trim((string) Config::get('ads.images.temporary_prefix', 'ads/tmp'), '/');
        $extension = $image->guessExtension() ?? $image->getClientOriginalExtension() ?? 'jpg';
        $filename = "{$token}.{$extension}";
        $path = $image->storeAs("{$prefix}/{$user->id}", $filename, $disk);

        if (! is_string($path)) {
            throw new RuntimeException("Failed to store temporary image for user {$user->id}.");
        }

        $metadata = [
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $image->getClientOriginalName(),
            'size_bytes' => (int) ($image->getSize() ?? 0),
        ];

        Cache::put(
            $this->cacheKey($token),
            $metadata,
            now()->addMinutes((int) Config::get('ads.images.temporary_ttl_minutes', 1440)),
        );

        return [
            'token' => $token,
            'preview_url' => Storage::disk($disk)->url($path),
            'original_name' => $metadata['original_name'],
            'size_bytes' => $metadata['size_bytes'],
        ];
    }

    public function belongsToUser(User $user, string $token): bool
    {
        $metadata = $this->metadata($token);

        return is_array($metadata) && (int) $metadata['user_id'] === $user->id;
    }

    public function deleteForUser(User $user, string $token): void
    {
        $metadata = $this->metadata($token);

        if (! is_array($metadata) || (int) $metadata['user_id'] !== $user->id) {
            return;
        }

        Storage::disk((string) $metadata['disk'])->delete((string) $metadata['path']);
        Cache::forget($this->cacheKey($token));
    }

    /**
     * @return array<string, mixed>
     */
    public function moveToAd(User $user, string $token, Ad $ad, int $position): array
    {
        $metadata = $this->metadata($token);

        if (! is_array($metadata) || (int) $metadata['user_id'] !== $user->id) {
            throw new RuntimeException("Temporary image {$token} is not available for user {$user->id}.");
        }

        $disk = (string) $metadata['disk'];
        $sourcePath = (string) $metadata['path'];
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $targetFilename = (string) Str::uuid().($extension !== '' ? ".{$extension}" : '');
        $targetPath = "ads/{$ad->id}/{$targetFilename}";

        if (! Storage::disk($disk)->move($sourcePath, $targetPath)) {
            throw new RuntimeException("Failed to move temporary image {$token} to ad {$ad->id}.");
        }

        Cache::forget($this->cacheKey($token));

        return [
            'disk' => $disk,
            'path' => $targetPath,
            'original_name' => (string) $metadata['original_name'],
            'size_bytes' => (int) $metadata['size_bytes'],
            'position' => $position,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function metadata(string $token): ?array
    {
        $cached = Cache::get($this->cacheKey($token));

        return is_array($cached) ? $cached : null;
    }

    private function cacheKey(string $token): string
    {
        return self::CACHE_PREFIX.$token;
    }
}
