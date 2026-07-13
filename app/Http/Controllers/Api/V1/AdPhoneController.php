<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Ads\RecordPhoneRevealAction;
use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class AdPhoneController extends Controller
{
    /**
     * Wydaje pełny numer dopiero po jawnym kliknięciu „Pokaż numer”.
     * Trasa jest ostro limitowana, więc masowe odpytywanie jest kosztowne.
     */
    public function __invoke(Ad $ad, RecordPhoneRevealAction $recordReveal): JsonResponse
    {
        $this->authorize('view', $ad);

        if ($ad->contact_phone === null) {
            return response()->json([
                'code' => 'AD_HAS_NO_PHONE',
                'message' => 'To ogłoszenie nie ma numeru telefonu.',
                'details' => (object) [],
            ], Response::HTTP_NOT_FOUND);
        }

        $recordReveal->execute($ad);

        return response()->json(['phone' => $ad->contact_phone]);
    }
}
