<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface FavoriteRepository
{
    /**
     * Dodaje ogłoszenie do ulubionych użytkownika. Idempotentne — ponowne
     * dodanie nie tworzy duplikatu.
     */
    public function add(User $user, Ad $ad): void;

    /**
     * Usuwa ogłoszenie z ulubionych. Idempotentne — brak wpisu to nie błąd.
     */
    public function remove(User $user, Ad $ad): void;

    /**
     * Ulubione użytkownika ograniczone do aktywnych ogłoszeń: wygasłe i
     * nieaktywne znikają z listy, choć wpis pozostaje w bazie.
     *
     * @return LengthAwarePaginator<int, Ad>
     */
    public function paginateActiveForUser(User $user): LengthAwarePaginator;

    /**
     * Identyfikatory aktywnych ulubionych — lekki zestaw do oznaczania serduszek
     * na liście po stronie frontu.
     *
     * @return list<int>
     */
    public function activeFavoriteIdsFor(User $user): array;

    /**
     * Użytkownicy obserwujący ogłoszenie — odbiorcy powiadomienia o zmianie.
     *
     * @return Collection<int, User>
     */
    public function usersFavoriting(Ad $ad): Collection;
}
