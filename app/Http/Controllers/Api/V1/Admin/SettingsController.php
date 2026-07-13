<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\SettingKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\Contracts\SettingsRepository;
use Illuminate\Http\JsonResponse;

final class SettingsController extends Controller
{
    public function show(SettingsRepository $settings): JsonResponse
    {
        return response()->json([
            'auto_approve_ads' => $settings->isEnabled(SettingKey::AutoApproveAds),
        ]);
    }

    public function update(UpdateSettingsRequest $request, SettingsRepository $settings): JsonResponse
    {
        $settings->setEnabled(SettingKey::AutoApproveAds, $request->boolean('auto_approve_ads'));

        return response()->json([
            'auto_approve_ads' => $settings->isEnabled(SettingKey::AutoApproveAds),
        ]);
    }
}
