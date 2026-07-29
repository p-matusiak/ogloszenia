<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class IssueTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],

            // Nazwa urządzenia trafia do listy sesji użytkownika, więc musi być
            // czytelna dla człowieka ("Pixel 8", "Samsung S23"), nie identyfikatorem.
            'device_name' => ['required', 'string', 'max:80'],
        ];
    }
}
