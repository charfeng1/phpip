<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiTokenController extends Controller
{
    /**
     * Issue a new API token for an authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string'],
        ]);

        $user = User::where('login', $validated['login'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => [trans('auth.failed')],
            ]);
        }

        $plainTextToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainTextToken);

        $token = $user->apiTokens()->create([
            'name' => $validated['device_name'] ?? $request->userAgent() ?? 'api-token',
            'token' => $hashedToken,
            'abilities' => $validated['abilities'] ?? ['*'],
            'expires_at' => $this->determineExpiration(),
        ]);

        return response()->json([
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->expires_at?->toISOString(),
        ], 201);
    }

    /**
     * Show the authenticated user.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'login' => $user->login,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->default_role,
            'language' => $user->language,
        ]);
    }

    /**
     * Revoke the current API token.
     */
    public function destroy(Request $request): JsonResponse
    {
        $token = ApiToken::findActiveToken($request->bearerToken());

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Token revoked',
        ]);
    }

    private function determineExpiration(): ?CarbonInterface
    {
        $minutes = (int) config('api.token_expiration_minutes', 43200);

        if ($minutes <= 0) {
            return null;
        }

        return now()->addMinutes($minutes);
    }
}
