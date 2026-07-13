<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\CategoryTreeBuilder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CategoriesController extends Controller
{
    public function __invoke(CategoryTreeBuilder $treeBuilder): AnonymousResourceCollection
    {
        $categories = $treeBuilder->build(
            Category::query()
                ->visible()
                ->orderBy('position')
                ->orderBy('name')
                ->get(),
        );

        return CategoryResource::collection($categories);
    }
}
