<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Exchanges credentials for a bearer token.
     *
     * Deliberately gives the same message whether the email is unknown or the
     * password is wrong, so this cannot be used to discover which addresses
     * have accounts. Attempts are logged either way, matching the web login.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        $ok = $user !== null && Hash::check($credentials['password'], $user->password);

        LoginLog::create([
            'user_id' => $user?->id,
            'successful' => $ok,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if (! $ok) {
            throw ValidationException::withMessages([
                'email' => ['Those credentials do not match our records.'],
            ]);
        }

        // Google-only accounts hold an unusable random password, so they must
        // not be able to sign in here by guessing it.
        if (! $user->has_password) {
            throw ValidationException::withMessages([
                'email' => ['This account signs in with Google. Please use the website.'],
            ]);
        }

        $user->ensureLinkedFamilyMember();

        $token = $user->createToken(
            $credentials['device_name'] ?? 'mobile',
            ['*'],
            now()->addDays(60),
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /** Revokes only the token that made this call, not every device. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
        ]);
    }
}
