<?php

declare(strict_types=1);

namespace App\Http\Requests\Ads;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

final class ReportAdRequest extends FormRequest
{
    /**
     * Guests may report ads; rate limiting guards the endpoint instead.
     */
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
            'reason' => ['required', 'string', Rule::in(Config::array('ads.report_reasons'))],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
