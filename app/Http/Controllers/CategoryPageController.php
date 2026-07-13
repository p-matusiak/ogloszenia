<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategorySlugHistory;
use App\Services\Seo\SeoPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Landing page kategorii. Adres jest płaski (`/kategoria/samochody`), a nie
 * zagnieżdżony: `categories.slug` jest unikalny globalnie, więc węzeł rozwiązuje
 * się bez znajomości głębokości, a przeniesienie gałęzi w drzewie przez
 * administratora nie unieważnia zaindeksowanego URL-a.
 */
final class CategoryPageController extends Controller
{
    public function __invoke(Request $request, string $slug, SeoPresenter $seo): Response|RedirectResponse
    {
        $category = Category::query()->with('ancestors')->where('slug', $slug)->first();

        if ($category === null) {
            return $this->redirectToCurrentSlug($slug);
        }

        // Ukryta kategoria nie ma strony. Nie 410 — węzeł wciąż istnieje i może
        // wrócić, a administrator ukrywa go zwykle na chwilę.
        if (! $category->is_visible) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->view('app', ['meta' => $seo->forCategory($category, $request)]);
    }

    private function redirectToCurrentSlug(string $slug): RedirectResponse
    {
        $history = CategorySlugHistory::query()->with('category')->where('slug', $slug)->first();

        if ($history === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return redirect()->route(
            'categories.show',
            ['slug' => $history->category->slug],
            Response::HTTP_MOVED_PERMANENTLY,
        );
    }
}
