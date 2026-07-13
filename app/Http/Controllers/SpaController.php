<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Seo\SeoPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Powłoka SPA dla tras opisanych w `config/seo.php`. Każda z nich jest wyliczona,
 * więc adres spoza listy kończy się prawdziwym 404 zamiast pustej strony ze
 * statusem 200.
 */
final class SpaController extends Controller
{
    public function __invoke(Request $request, SeoPresenter $seo): View
    {
        return view('app', ['meta' => $seo->forRequest($request)]);
    }
}
