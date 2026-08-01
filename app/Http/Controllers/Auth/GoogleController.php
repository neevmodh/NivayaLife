<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Existing accounts log straight in. A brand-new Google identity is never
     * turned into a User row here — it's handed to the registration form
     * (name/email pre-filled and already verified) so an abandoned Google
     * sign-up leaves no account behind either.
     */
    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            Auth::login($user, remember: true);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        session(['wizard.google' => [
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'New user',
            'email' => $googleUser->getEmail(),
        ]]);

        return redirect()->route('register');
    }
}
