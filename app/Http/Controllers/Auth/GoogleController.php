<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * The mobile app can't receive a session cookie, so it flags this as a
     * mobile-initiated flow via the session (survives the round-trip to
     * Google and back) rather than a query param Socialite's own `state`
     * handling would otherwise have to carry.
     */
    public function redirect(Request $request): RedirectResponse
    {
        if ($request->boolean('mobile')) {
            session(['oauth_mobile' => true]);
        }

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
        $isMobile = session()->pull('oauth_mobile', false);

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            if ($isMobile) {
                $user->ensureLinkedFamilyMember();
                $token = $user->createToken('mobile-google', ['*'], now()->addDays(60));

                return redirect(config('services.mobile_app.scheme')."://auth-callback?token={$token->plainTextToken}");
            }

            Auth::login($user, remember: true);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        if ($isMobile) {
            // No account to hand a token for — the mobile app doesn't have a
            // native registration wizard (that's web-only), so send it back
            // with enough context to tell the person to sign up on the web.
            return redirect(config('services.mobile_app.scheme')."://auth-callback?error=no_account&email=".urlencode($googleUser->getEmail() ?? ''));
        }

        session(['wizard.google' => [
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'New user',
            'email' => $googleUser->getEmail(),
        ]]);

        return redirect()->route('register');
    }
}
