<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Repositories\Contracts\AdRepository;
use App\Services\Seo\SeoPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Jedyna strona renderowana z danych ogłoszenia. Odpowiada za trzy rzeczy,
 * których catch-all SPA nie potrafił: meta tagi i dane strukturalne w pierwszej
 * odpowiedzi HTML, prawdziwy status HTTP dla ogłoszeń, których już nie ma, oraz
 * 301 ze starego adresu po zmianie tytułu.
 */
final class AdPageController extends Controller
{
    public function __invoke(Request $request, string $slug, SeoPresenter $seo, AdRepository $ads): Response|RedirectResponse
    {
        $ad = $ads->findDetailBySlug($slug);

        if ($ad === null) {
            return $this->redirectToCurrentSlug($slug, $ads);
        }

        // Autor i administrator muszą widzieć swoje ogłoszenie także przed
        // moderacją; `forAd()` oznaczy taką stronę jako `noindex`.
        $isReadable = $ad->isPubliclyVisible() || $request->user()?->can('view', $ad) === true;

        if (! $isReadable) {
            return response()->view(
                'app',
                [],
                $this->missingStatus($ad),
            );
        }

        return response()->view(
            'app',
            ['meta' => $seo->forAd($ad)],
            Response::HTTP_OK,
        );
    }

    private function redirectToCurrentSlug(string $slug, AdRepository $ads): RedirectResponse
    {
        $ad = $ads->findByHistoricalSlug($slug);

        if ($ad === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($ad->isGone()) {
            abort(Response::HTTP_GONE);
        }

        if (! $ad->isPubliclyVisible()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return redirect()->route(
            'ads.show',
            ['slug' => $ad->slug],
            Response::HTTP_MOVED_PERMANENTLY,
        );
    }

    /**
     * 410 mówi wyszukiwarce „ten adres był poprawny, zasobu już nie ma” i usuwa
     * go z indeksu szybciej niż 404. Ogłoszenie oczekujące na moderację jeszcze
     * nigdy nie było publiczne, więc dla świata po prostu nie istnieje.
     */
    private function missingStatus(Ad $ad): int
    {
        return $ad->isGone() ? Response::HTTP_GONE : Response::HTTP_NOT_FOUND;
    }
}
