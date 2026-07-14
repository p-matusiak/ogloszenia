<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepository
{
    public function findById(int $id): ?Category;

    public function hasChildren(int $categoryId): bool;

    public function isVisibleLeaf(int $categoryId): bool;

    /**
     * @return Collection<int, Category>
     */
    public function listVisibleOrdered(): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Category;
}
