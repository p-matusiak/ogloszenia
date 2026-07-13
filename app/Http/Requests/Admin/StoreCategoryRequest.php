<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            // null creates a root category; any existing node may be a parent,
            // so the tree is not limited to two levels.
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_visible' => ['nullable', 'boolean'],
        ];
    }
}
