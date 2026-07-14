<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Repositories\Contracts\CategoryRepository;
use App\Support\CategoryTreeBuilder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CategoriesController extends Controller
{
    public function __invoke(CategoryRepository $categories, CategoryTreeBuilder $treeBuilder): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            $treeBuilder->build($categories->listVisibleOrdered()),
        );
    }
}
