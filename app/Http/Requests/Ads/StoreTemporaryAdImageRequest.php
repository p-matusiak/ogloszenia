<?php

declare(strict_types=1);

namespace App\Http\Requests\Ads;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;

final class StoreTemporaryAdImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

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
            'images' => ['required', 'array', 'min:1', 'max:'.$images['max_per_ad']],
            'images.*' => ['image', 'mimes:'.implode(',', $images['mimes']), 'max:'.$images['max_size_kb']],
        ];
    }
}
