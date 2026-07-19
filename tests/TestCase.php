<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $appUrl = rtrim((string) config('app.url'), '/');

        $this->withHeaders([
            'Origin' => $appUrl,
            'Referer' => $appUrl.'/',
        ]);

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
    }
}
