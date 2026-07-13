<?php

declare(strict_types=1);

namespace App\Http\Requests\Favorites;

use Illuminate\Foundation\Http\FormRequest;

final class FavoriteAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Dodanie do ulubionych nie przyjmuje żadnych danych — ogłoszenie pochodzi
     * z wiązania trasy, a właściwa reguła („tylko aktywne”) żyje w akcji.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
