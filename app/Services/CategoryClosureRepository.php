<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Maintains the category_closure table. Every write here must run inside the
 * same transaction as the categories row it describes, or the tree is corrupt.
 */
final class CategoryClosureRepository
{
    public const string TABLE = 'category_closure';

    /**
     * Links a freshly created node: its own zero-depth row, plus one row for
     * every ancestor of its parent, each one level deeper.
     */
    public function insertNode(int $nodeId, ?int $parentId): void
    {
        DB::table(self::TABLE)->insert([
            'ancestor_id' => $nodeId,
            'descendant_id' => $nodeId,
            'depth' => 0,
        ]);

        if ($parentId === null) {
            return;
        }

        DB::insert(
            'INSERT INTO '.self::TABLE.' (ancestor_id, descendant_id, depth)
             SELECT ancestor_id, ?, depth + 1 FROM '.self::TABLE.' WHERE descendant_id = ?',
            [$nodeId, $parentId],
        );
    }

    /**
     * Re-parents a node together with its whole subtree.
     */
    public function moveNode(int $nodeId, ?int $newParentId): void
    {
        $this->detachSubtree($nodeId);

        if ($newParentId === null) {
            return;
        }

        // Cross join every ancestor of the new parent with every descendant of
        // the moved node; depths add up across the new edge.
        DB::insert(
            'INSERT INTO '.self::TABLE.' (ancestor_id, descendant_id, depth)
             SELECT supertree.ancestor_id, subtree.descendant_id, supertree.depth + subtree.depth + 1
             FROM '.self::TABLE.' AS supertree
             CROSS JOIN '.self::TABLE.' AS subtree
             WHERE supertree.descendant_id = ? AND subtree.ancestor_id = ?',
            [$newParentId, $nodeId],
        );
    }

    /**
     * The node itself plus every node beneath it.
     *
     * @return list<int>
     */
    public function subtreeIds(int $nodeId): array
    {
        return DB::table(self::TABLE)
            ->where('ancestor_id', $nodeId)
            ->pluck('descendant_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function isDescendant(int $ancestorId, int $possibleDescendantId): bool
    {
        return DB::table(self::TABLE)
            ->where('ancestor_id', $ancestorId)
            ->where('descendant_id', $possibleDescendantId)
            ->where('depth', '>', 0)
            ->exists();
    }

    /**
     * Cuts every link between the subtree and ancestors above it, while leaving
     * the links inside the subtree intact.
     */
    private function detachSubtree(int $nodeId): void
    {
        DB::delete(
            'DELETE FROM '.self::TABLE.'
             WHERE descendant_id IN (SELECT descendant_id FROM '.self::TABLE.' WHERE ancestor_id = ?)
               AND ancestor_id NOT IN (SELECT descendant_id FROM '.self::TABLE.' WHERE ancestor_id = ?)',
            [$nodeId, $nodeId],
        );
    }
}
