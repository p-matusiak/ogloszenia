<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserSlugHistory;
use App\Repositories\Contracts\UserRepository;
use App\Services\Seo\SeoPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Profil sprzedawcy z meta tagami w pierwszej odpowiedzi HTML oraz 301 ze
 * starego sluga po zmianie nazwy konta.
 */
final class SellerPageController extends Controller
{
    public function __invoke(string $slug, UserRepository $users, SeoPresenter $seo): Response|RedirectResponse
    {
        if (ctype_digit($slug)) {
            return $this->redirectNumericId((int) $slug, $users);
        }

        $seller = $users->findPublicSellerBySlug($slug);

        if ($seller === null) {
            return $this->redirectToCurrentSlug($slug);
        }

        return response()->view('app', ['meta' => $seo->forSeller($seller)]);
    }

    private function redirectNumericId(int $sellerId, UserRepository $users): RedirectResponse
    {
        $seller = $users->findById($sellerId);

        if ($seller === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return redirect()->route(
            'sellers.show',
            ['slug' => $seller->slug],
            Response::HTTP_MOVED_PERMANENTLY,
        );
    }

    private function redirectToCurrentSlug(string $slug): RedirectResponse
    {
        $history = UserSlugHistory::query()->with('user')->where('slug', $slug)->first();

        if ($history === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return redirect()->route(
            'sellers.show',
            ['slug' => $history->user->slug],
            Response::HTTP_MOVED_PERMANENTLY,
        );
    }
}
