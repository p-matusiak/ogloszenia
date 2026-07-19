<?php

declare(strict_types=1);

namespace App\Http\Requests\Ads;

use App\Enums\AdCondition;
use App\Enums\DeliveryMethod;
use App\Models\User;
use App\Repositories\Contracts\CategoryRepository;
use App\Support\TemporaryAdImageStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Guaranteed non-null by authorize().
     */
    public function author(): User
    {
        $user = $this->user();

        assert($user instanceof User);

        return $user;
    }

    /**
     * @return list<UploadedFile>
     */
    public function images(): array
    {
        $files = $this->file('images', []);

        return is_array($files) ? array_values($files) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $images = Config::array('ads.images');

        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:10000'],

            // The leaf node, e.g. "Samochody". Its ancestors come from the
            // closure table, so the parent category is never posted.
            'category_id' => ['required', 'integer', 'exists:categories,id'],

            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'is_negotiable' => ['nullable', 'boolean'],
            'condition' => ['nullable', Rule::enum(AdCondition::class)],

            'location' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'required_with:location,longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:location,latitude', 'numeric', 'between:-180,180'],

            'delivery_methods' => ['array'],
            'delivery_methods.*' => [Rule::enum(DeliveryMethod::class)],

            // Mapa metoda → cena. Klucz spoza wybranych metod nie ma sensu.
            'delivery_prices' => ['array'],
            'delivery_prices.*' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],

            'use_custom_phone' => ['nullable', 'boolean'],
            'contact_phone' => ['nullable', 'required_if:use_custom_phone,true', 'string', 'max:32'],

            'accept_terms' => ['accepted'],

            'images' => ['array', 'max:'.$images['max_per_ad']],
            'images.*' => ['image', 'mimes:'.implode(',', $images['mimes']), 'max:'.$images['max_size_kb']],
            'temporary_images' => ['array', 'max:'.$images['max_per_ad']],
            'temporary_images.*' => ['string'],
        ];
    }

    /**
     * @return list<string>
     */
    public function temporaryImages(): array
    {
        $tokens = $this->input('temporary_images', []);

        return is_array($tokens)
            ? array_values(array_filter($tokens, static fn (mixed $token): bool => is_string($token) && $token !== ''))
            : [];
    }

    /**
     * @return list<callable(Validator): void>
     */
    protected function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateCategoryIsLeaf($validator),
            fn (Validator $validator) => $this->validateDeliveryPriceKeys($validator),
            fn (Validator $validator) => $this->validateTemporaryImages($validator),
            fn (Validator $validator) => $this->validateTotalImageCount($validator),
        ];
    }

    /**
     * Ads belong at the bottom of the tree. Filing one directly under
     * "Motoryzacja" would make it invisible to every subcategory filter.
     */
    private function validateCategoryIsLeaf(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $categories = app(CategoryRepository::class);
        $category = $categories->findById($this->integer('category_id'));

        if ($category === null) {
            return;
        }

        if ($categories->hasChildren($category->id)) {
            $validator->errors()->add('category_id', 'Choose a subcategory, not a top-level category.');
        }
    }

    /**
     * Cena dostawy metodą, której autor nie zaznaczył, byłaby martwym rekordem.
     */
    private function validateDeliveryPriceKeys(Validator $validator): void
    {
        /** @var array<string, mixed> $prices */
        $prices = $this->input('delivery_prices', []);

        /** @var list<string> $methods */
        $methods = $this->input('delivery_methods', []);

        foreach (array_keys($prices) as $method) {
            if (! in_array($method, $methods, true)) {
                $validator->errors()->add(
                    "delivery_prices.{$method}",
                    'Cena dotyczy metody dostawy, która nie została wybrana.',
                );
            }
        }
    }

    private function validateTemporaryImages(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $storage = app(TemporaryAdImageStorage::class);

        foreach ($this->temporaryImages() as $index => $token) {
            if (! $storage->belongsToUser($this->author(), $token)) {
                $validator->errors()->add(
                    "temporary_images.{$index}",
                    'Jedno ze zdjęć tymczasowych wygasło albo nie należy do bieżącego użytkownika.',
                );
            }
        }
    }

    private function validateTotalImageCount(Validator $validator): void
    {
        $maximum = Config::integer('ads.images.max_per_ad');
        $count = count($this->images()) + count($this->temporaryImages());

        if ($count > $maximum) {
            $validator->errors()->add('images', "An ad may hold at most {$maximum} images.");
        }
    }
}
