<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;

final class AdNotMessageableException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Only active ads can be messaged.');
    }

    public function errorCode(): string
    {
        return 'AD_NOT_MESSAGEABLE';
    }

    public function httpStatus(): int
    {
        return Response::HTTP_CONFLICT;
    }
}
