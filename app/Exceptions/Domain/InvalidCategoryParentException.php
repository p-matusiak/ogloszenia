<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

final class InvalidCategoryParentException extends DomainException
{
    public function __construct()
    {
        parent::__construct('A category cannot be moved beneath itself or one of its own descendants.');
    }

    public function errorCode(): string
    {
        return 'CATEGORY_INVALID_PARENT';
    }
}
