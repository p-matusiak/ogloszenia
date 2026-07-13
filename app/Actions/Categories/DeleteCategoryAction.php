<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Exceptions\Domain\CategoryInUseException;
use App\Models\Ad;
use App\Models\Category;
use App\Services\CategoryClosureRepository;

final readonly class DeleteCategoryAction
{
    public function __construct(private CategoryClosureRepository $closure) {}

    /**
     * Deleting a node deletes its subtree, so the guard has to consider ads
     * anywhere beneath it, not just ads attached to the node itself.
     *
     * @throws CategoryInUseException
     */
    public function execute(Category $category): void
    {
        $adsCount = Ad::query()
            ->whereIn('category_id', $this->closure->subtreeIds($category->id))
            ->count();

        if ($adsCount > 0) {
            throw new CategoryInUseException($adsCount);
        }

        // Children cascade via parent_id; closure rows cascade via both FKs.
        $category->delete();
    }
}
