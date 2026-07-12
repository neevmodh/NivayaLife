<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Existing accounts log straight in. A brand-new Google identity is never
     * turned into a User row here — it's handed to the registration wizard
     * (landing on step 2, since name/email are already verified) so an
     * abandoned Google sign-up leaves no account behind either.
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

        $this->preloadGoogleAvatar($googleUser->getAvatar());

        return redirect()->route('register');
    }

    /**
     * Best-effort fetch of the Google profile photo into the same tmp slot
     * step 2's camera capture uses, so returning users see a preview already
     * in place — non-fatal if it fails, they can still capture/upload normally.
     */
    private function preloadGoogleAvatar(?string $avatarUrl): void
    {
        if (! $avatarUrl) {
            return;
        }

        try {
            $response = Http::timeout(5)->get($avatarUrl);

            if ($response->successful()) {
                $path = 'tmp-registration/'.session()->getId().'.jpg';
                Storage::disk('local')->put($path, $response->body());
                session(['wizard.step2.avatar_tmp_path' => $path]);
            }
        } catch (\Throwable) {
            // Non-fatal — the wizard's camera capture step covers this case.
        }
    }
}
