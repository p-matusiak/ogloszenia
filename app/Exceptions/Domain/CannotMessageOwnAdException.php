<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;

final class CannotMessageOwnAdException extends DomainException
{
    public function __construct()
    {
        parent::__construct('You cannot message your own ad.');
    }

    public function errorCode(): string
    {
        return 'CANNOT_MESSAGE_OWN_AD';
    }

    public function httpStatus(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}
