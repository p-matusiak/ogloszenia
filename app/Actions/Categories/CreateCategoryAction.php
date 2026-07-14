<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;
use App\Services\CategoryClosureRepository;
use App\Support\CategorySlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class CreateCategoryAction
{
    public function __construct(
        private CategoryRepository $categories,
        private CategoryClosureRepository $closure,
        private CategorySlugGenerator $slugGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Category
    {
        return DB::transaction(function () use ($data): Category {
            $data['slug'] = $this->slugGenerator->generate((string) $data['name']);
            $category = $this->categories->create($data);

            $this->closure->insertNode($category->id, $category->parent_id);

            return $category;
        });
    }
}
