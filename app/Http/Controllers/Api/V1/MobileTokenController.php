<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\IssueTokenRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wydawanie tokenów dla klientów natywnych (Android/iOS).
 *
 * Przeglądarkowe SPA korzysta z sesji (Sanctum stateful), ale aplikacja mobilna
 * nie ma ciasteczek ani CSRF i musi zostać zalogowana dłużej niż trwa sesja
 * (120 minut). Dlatego osobna ścieżka: token osobisty w nagłówku Bearer.
 */
final class MobileTokenController extends Controller
{
    public function __construct(private readonly UserRepository $users) {}

    /**
     * @throws ValidationException
     */
    public function store(IssueTokenRequest $request): JsonResponse
    {
        $user = $this->users->findByEmail($request->string('email')->toString());

        // Hash::check zawsze na stałym haszu, gdy konta nie ma — inaczej różnica
        // czasu odpowiedzi zdradza, które adresy są zarejestrowane.
        $hash = $user instanceof User ? $user->password : Hash::make('nieistniejace-konto');

        if (! Hash::check($request->string('password')->toString(), $hash) || ! $user instanceof User) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Jedno urządzenie = jeden token. Ponowne logowanie na tym samym
        // telefonie nie może zostawiać po sobie sierot, których użytkownik
        // nigdy nie unieważni.
        $device = $request->string('device_name')->toString();
        $user->tokens()->where('name', $device)->delete();

        return response()->json([
            'token' => $user->createToken($device)->plainTextToken,
            'data' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request): Response
    {
        $token = $request->user()?->currentAccessToken();

        // Wylogowanie dotyczy wyłącznie bieżącego urządzenia; pozostałe sesje
        // użytkownika zostają nietknięte.
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }
}
