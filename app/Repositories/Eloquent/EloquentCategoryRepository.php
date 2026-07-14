<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function findById(int $id): ?Category
    {
        return Category::query()->find($id);
    }

    public function hasChildren(int $categoryId): bool
    {
        return Category::query()
            ->where('parent_id', $categoryId)
            ->exists();
    }

    public function isVisibleLeaf(int $categoryId): bool
    {
        $category = Category::query()
            ->visible()
            ->find($categoryId);

        if ($category === null) {
            return false;
        }

        return ! $this->hasChildren($category->id);
    }

    public function listVisibleOrdered(): Collection
    {
        return Category::query()
            ->visible()
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    public function create(array $attributes): Category
    {
        return Category::query()->create($attributes);
    }
}
