<?php

declare(strict_types=1);

namespace App\Http\Requests\Ads;

use App\Enums\AdCondition;
use App\Enums\AdSort;
use App\Enums\DeliveryMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class IndexAdRequest extends FormRequest
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
            'q' => ['nullable', 'string', 'max:120'],

            // Oba wskazują węzeł tego samego drzewa; `subcategory` jest
            // głębszy i wygrywa, gdy przyjdą razem.
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'subcategory' => ['nullable', 'string', 'exists:categories,slug'],

            'location' => ['nullable', 'string', 'max:120'],

            // `gte:price_min` nie działa, gdy price_min w ogóle nie przyszło —
            // porównanie z brakującym polem zawodzi. Sprawdzamy to w after().
            'price_min' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],

            'negotiable' => ['nullable', 'boolean'],
            'free' => ['nullable', 'boolean'],

            // Listy rozdzielone przecinkami, np. condition=new,used
            'condition' => ['nullable', 'string'],
            'delivery' => ['nullable', 'string'],

            'sort' => ['nullable', Rule::in(AdSort::values())],

            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    protected function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateList($validator, 'condition', AdCondition::values()),
            fn (Validator $validator) => $this->validateList($validator, 'delivery', DeliveryMethod::values()),
            fn (Validator $validator) => $this->validatePriceRange($validator),
        ];
    }

    private function validatePriceRange(Validator $validator): void
    {
        $min = $this->query('price_min');
        $max = $this->query('price_max');

        if ($min === null || $max === null) {
            return;
        }

        if ((float) $max < (float) $min) {
            $validator->errors()->add('price_max', 'Cena maksymalna nie może być niższa od minimalnej.');
        }
    }

    /**
     * Nieznana wartość w liście musi dać 422, a nie zostać po cichu zignorowana.
     *
     * @param  array<int, string>  $allowed
     */
    private function validateList(Validator $validator, string $field, array $allowed): void
    {
        $raw = $this->string($field)->toString();

        if ($raw === '') {
            return;
        }

        foreach (explode(',', $raw) as $value) {
            if (! in_array($value, $allowed, true)) {
                $validator->errors()->add($field, "Nieznana wartość: {$value}.");

                return;
            }
        }
    }

    /**
     * `safe()` oddaje „1” jako string, więc flagi rzutujemy na bool po scaleniu.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_merge($this->safe()->all(), [
            'negotiable' => $this->boolean('negotiable'),
            'free' => $this->boolean('free'),
        ]);
    }
}
