<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Config;

final class AppVersion
{
    private static ?string $resolved = null;

    public static function resolve(): string
    {
        $configuredVersion = trim((string) Config::get('app.version', ''));

        if ($configuredVersion !== '') {
            return $configuredVersion;
        }

        if (self::$resolved !== null) {
            return self::$resolved;
        }

        return self::$resolved = self::resolveFromGit() ?? 'unknown';
    }

    private static function resolveFromGit(): ?string
    {
        $gitDirectory = base_path('.git');

        if (! is_dir($gitDirectory) && ! is_file($gitDirectory)) {
            return null;
        }

        $headReference = @file_get_contents($gitDirectory.'/HEAD');

        if (! is_string($headReference)) {
            return null;
        }

        $headReference = trim($headReference);

        if ($headReference === '') {
            return null;
        }

        if (! str_starts_with($headReference, 'ref: ')) {
            return substr($headReference, 0, 12);
        }

        $referencePath = $gitDirectory.'/'.substr($headReference, 5);
        $commitHash = @file_get_contents($referencePath);

        if (! is_string($commitHash)) {
            return null;
        }

        $commitHash = trim($commitHash);

        return $commitHash === '' ? null : substr($commitHash, 0, 12);
    }
}
