<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Symfony\Component\HttpFoundation\Response;

final class CategoryInUseException extends DomainException
{
    public function __construct(private readonly int $adsCount)
    {
        parent::__construct('It still holds ads and cannot be deleted. Hide it instead.');
    }

    public function errorCode(): string
    {
        return 'CATEGORY_IN_USE';
    }

    public function httpStatus(): int
    {
        return Response::HTTP_CONFLICT;
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return ['ads_count' => $this->adsCount];
    }
}
