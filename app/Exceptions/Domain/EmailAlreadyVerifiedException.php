<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;

final class EmailAlreadyVerifiedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This email address has already been verified.');
    }

    public function errorCode(): string
    {
        return 'EMAIL_ALREADY_VERIFIED';
    }

    public function httpStatus(): int
    {
        return Response::HTTP_CONFLICT;
    }
}
