<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     *
     * A Google-only account's stored password is a random string nobody —
     * including the account owner — knows, so 'current_password' can never
     * be satisfied for a first-time password set. has_password tracks
     * whether there's actually a real, known password to confirm against;
     * once this succeeds, the account has one either way.
     */
    public function update(Request $request): RedirectResponse
    {
        $rules = ['password' => ['required', Password::defaults(), 'confirmed']];

        if ($request->user()->has_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validateWithBag('updatePassword', $rules);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'has_password' => true,
        ]);

        return back()->with('status', 'password-updated');
    }
}
