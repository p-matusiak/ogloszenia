<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;

/**
 * Raised instead of Laravel's own 403 so the SPA receives the shared error
 * envelope and can tell "not verified" apart from "not allowed".
 */
final class EmailNotVerifiedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Potwierdź adres e-mail, aby wykonać tę operację.');
    }

    public function errorCode(): string
    {
        return 'EMAIL_NOT_VERIFIED';
    }

    public function httpStatus(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
