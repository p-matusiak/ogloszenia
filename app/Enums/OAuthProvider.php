<?php

declare(strict_types=1);

namespace App\Enums;

enum OAuthProvider: string
{
    case Google = 'google';
    case Facebook = 'facebook';

    public static function tryFromDriver(string $driver): ?self
    {
        return self::tryFrom($driver);
    }
}
