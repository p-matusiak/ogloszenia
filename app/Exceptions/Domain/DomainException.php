<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base for business-rule violations. Rendered into the shared API error
 * envelope by the handler registered in bootstrap/app.php.
 */
abstract class DomainException extends RuntimeException
{
    abstract public function errorCode(): string;

    public function httpStatus(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return [];
    }
}
