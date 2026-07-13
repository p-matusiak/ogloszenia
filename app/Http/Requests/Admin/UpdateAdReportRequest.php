<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAdReportRequest extends FormRequest
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
            // A moderator resolves a report; they never move it back to pending.
            'status' => ['required', Rule::in([ReportStatus::Reviewed->value, ReportStatus::Dismissed->value])],
        ];
    }
}
