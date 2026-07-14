<?php

declare(strict_types=1);

namespace App\Http\Requests\Ads;

use Illuminate\Foundation\Http\FormRequest;

final class SuggestAdCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
        ];
    }
}
