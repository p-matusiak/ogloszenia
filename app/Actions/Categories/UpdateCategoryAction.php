<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Exceptions\Domain\InvalidCategoryParentException;
use App\Models\Category;
use App\Services\CategoryClosureRepository;
use App\Support\CategorySlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCategoryAction
{
    public function __construct(
        private CategoryClosureRepository $closure,
        private CategorySlugGenerator $slugGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidCategoryParentException
     */
    public function execute(Category $category, array $data): Category
    {
        $newParentId = array_key_exists('parent_id', $data)
            ? $this->toNullableInt($data['parent_id'])
            : $category->parent_id;

        $this->guardAgainstCycle($category, $newParentId);

        return DB::transaction(function () use ($category, $data, $newParentId): Category {
            $moved = $newParentId !== $category->parent_id;
            $previousSlug = $category->slug;

            if ((string) $data['name'] !== $category->name) {
                $data['slug'] = $this->slugGenerator->generate((string) $data['name'], $category->id);
            }

            $category->update($data);

            $this->archivePreviousSlug($category, $previousSlug);

            if ($moved) {
                $this->closure->moveNode($category->id, $newParentId);
            }

            return $category;
        });
    }

    /**
     * Stary adres strony kategorii oddaje odtąd 301. Gdy administrator cofa
     * nazwę do poprzedniego brzmienia, odzyskany slug musi zniknąć z historii,
     * bo inaczej zderzy się z unikalnym indeksem.
     */
    private function archivePreviousSlug(Category $category, string $previousSlug): void
    {
        if ($category->slug === $previousSlug) {
            return;
        }

        $category->slugHistories()->where('slug', $category->slug)->delete();
        $category->slugHistories()->create(['slug' => $previousSlug]);
    }

    /**
     * @throws InvalidCategoryParentException
     */
    private function guardAgainstCycle(Category $category, ?int $newParentId): void
    {
        if ($newParentId === null) {
            return;
        }

        if ($newParentId === $category->id || $this->closure->isDescendant($category->id, $newParentId)) {
            throw new InvalidCategoryParentException;
        }
    }

    private function toNullableInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
