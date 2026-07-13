<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Models\Category;
use App\Services\CategoryClosureRepository;
use App\Support\CategorySlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class CreateCategoryAction
{
    public function __construct(
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
            $category = Category::query()->create($data);

            $this->closure->insertNode($category->id, $category->parent_id);

            return $category;
        });
    }
}
