<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

/**
 * Spłaszcza widoczne drzewo kategorii do liści z pełną ścieżką — podpowiedź AI
 * i walidacja sugestii muszą operować wyłącznie na liściach.
 */
final class CategoryLeafCatalog
{
    private const string SEPARATOR = ' > ';

    /**
     * @param  Collection<int, Category>  $roots
     * @return list<array{id: int, path: string}>
     */
    public function leavesFromRoots(Collection $roots): array
    {
        return $roots->flatMap(fn (Category $root): array => $this->leavesOf($root, []))->all();
    }

    /**
     * @param  list<string>  $ancestors
     * @return list<array{id: int, path: string}>
     */
    private function leavesOf(Category $node, array $ancestors): array
    {
        $children = $node->relationLoaded('children')
            ? $node->children
            : new Collection;

        if ($children->isEmpty()) {
            return [[
                'id' => $node->id,
                'path' => implode(self::SEPARATOR, [...$ancestors, $node->name]),
            ]];
        }

        return $children
            ->flatMap(fn (Category $child): array => $this->leavesOf($child, [...$ancestors, $node->name]))
            ->all();
    }
}
