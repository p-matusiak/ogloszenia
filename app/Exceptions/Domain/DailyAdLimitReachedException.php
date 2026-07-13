<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;

final class DailyAdLimitReachedException extends DomainException
{
    public function __construct(private readonly int $limit)
    {
        parent::__construct("You may publish at most {$limit} ads per day.");
    }

    public function errorCode(): string
    {
        return 'ADS_DAILY_LIMIT_REACHED';
    }

    public function httpStatus(): int
    {
        return Response::HTTP_TOO_MANY_REQUESTS;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return ['limit' => $this->limit];
    }
}
