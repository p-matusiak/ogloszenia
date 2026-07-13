<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final class CategoryTreeBuilder
{
    private const ROOT_KEY = 'root';

    /**
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, Category>
     */
    public function build(Collection $categories): Collection
    {
        $childrenByParent = $categories->groupBy(
            fn (Category $category): string => $category->parent_id === null
                ? self::ROOT_KEY
                : (string) $category->parent_id,
        );

        $attachChildren = function (?int $parentId) use (&$attachChildren, $childrenByParent): Collection {
            $key = $parentId === null ? self::ROOT_KEY : (string) $parentId;

            /** @var Collection<int, Category> $nodes */
            $nodes = $childrenByParent->get($key, new Collection);

            return $nodes->map(function (Category $category) use (&$attachChildren): Category {
                $category->setRelation('children', $attachChildren($category->id));

                return $category;
            });
        };

        return $attachChildren(null);
    }
}
