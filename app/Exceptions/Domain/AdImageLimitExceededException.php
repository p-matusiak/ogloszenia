<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

final class AdImageLimitExceededException extends DomainException
{
    public function __construct(
        private readonly int $maximum,
        private readonly int $current,
    ) {
        parent::__construct("An ad may hold at most {$maximum} images.");
    }

    public function errorCode(): string
    {
        return 'AD_IMAGE_LIMIT_EXCEEDED';
    }

    /**
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return ['maximum' => $this->maximum, 'current' => $this->current];
    }
}
