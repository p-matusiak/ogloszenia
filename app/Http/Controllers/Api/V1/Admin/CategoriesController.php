<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Categories\CreateCategoryAction;
use App\Actions\Categories\DeleteCategoryAction;
use App\Actions\Categories\UpdateCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\CategoryTreeBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class CategoriesController extends Controller
{
    public function index(CategoryTreeBuilder $treeBuilder): AnonymousResourceCollection
    {
        $categories = $treeBuilder->build(
            Category::query()
                ->orderBy('position')
                ->orderBy('name')
                ->get(),
        );

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request, CreateCategoryAction $createCategory): JsonResponse
    {
        $category = $createCategory->execute($request->validated());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category,
        UpdateCategoryAction $updateCategory,
    ): CategoryResource {
        return new CategoryResource($updateCategory->execute($category, $request->validated()));
    }

    public function destroy(Category $category, DeleteCategoryAction $deleteCategory): Response
    {
        $deleteCategory->execute($category);

        return response()->noContent();
    }
}
