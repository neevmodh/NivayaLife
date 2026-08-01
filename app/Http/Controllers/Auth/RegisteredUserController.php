<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\RegisterRequest;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\FamilyMember;
use App\Models\IdCard;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Signup is a single form: full name, email, password, phone, and gender —
 * everything else (photo, address, health info, blood group, date of
 * birth, emergency contact) is optional and filled in later from /profile,
 * which already has its own separately-validated edit forms for each of
 * those. Nothing here writes to the database until the whole form passes
 * validation and the transaction commits.
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $google = session('wizard.google');

        return view('auth.register', [
            'google' => $google,
        ]);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $email = (string) $request->query('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['available' => null]);
        }

        return response()->json([
            'available' => ! User::where('email', $email)->exists(),
        ]);
    }

    public function store(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $google = session('wizard.google');

        $user = DB::transaction(function () use ($data, $google, $request) {
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $google['email'] ?? $data['email'],
                'password' => $google ? Str::password(32) : Hash::make($data['password']),
                'google_id' => $google['google_id'] ?? null,
                'phone' => $data['phone'],
                'email_verified_at' => $google ? now() : null,
            ]);

            $familyMember = FamilyMember::create([
                'primary_account_id' => $user->id,
                'linked_user_id' => $user->id,
                'relation' => 'self',
                'full_name' => $data['full_name'],
                'gender' => $data['gender'],
                'access_type' => 'linked',
                'status' => 'active',
            ]);

            foreach (['account_creation', 'upload', 'ai_processing'] as $consentType) {
                if ($data['consent_'.$consentType] ?? false) {
                    Consent::grant($user, $consentType, $request);
                }
            }

            IdCard::generateCard($familyMember);

            AuditLog::record('account_creation', 'User', $user->id, $user->id, $familyMember->id);

            return $user;
        });

        $request->session()->forget('wizard');

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->flash('just_registered', $user->name);

        return response()->json(['success' => true, 'redirect' => route('dashboard')]);
    }
}
