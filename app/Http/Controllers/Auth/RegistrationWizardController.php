<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\Step1Request;
use App\Http\Requests\Registration\Step3Request;
use App\Http\Requests\Registration\Step4Request;
use App\Http\Requests\Registration\Step5Request;
use App\Models\Allergy;
use App\Models\AuditLog;
use App\Models\BmiLog;
use App\Models\Consent;
use App\Models\FamilyMember;
use App\Models\IdCard;
use App\Models\Medication;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationWizardController extends Controller
{
    private const TOTAL_STEPS = 5;

    /**
     * Show the wizard shell. Resumes at the furthest step the session has
     * already completed, so a page refresh never loses progress.
     */
    public function create(): View
    {
        $wizard = session('wizard', []);
        $resumeStep = min(max($wizard['furthest_step'] ?? 1, 1), self::TOTAL_STEPS);

        $avatarTmpPath = $wizard['step2']['avatar_tmp_path'] ?? null;

        return view('auth.register', [
            'wizard' => $wizard,
            'resumeStep' => $resumeStep,
            'hasPhoto' => $avatarTmpPath !== null && Storage::disk('local')->exists($avatarTmpPath),
        ]);
    }

    /**
     * The wizard never reloads the page between steps — Alpine just shows/
     * hides pre-rendered step markup — so step 5's review cards can't rely
     * on server-rendered PHP variables (those reflect the session at the
     * initial GET /register, before anything was typed). This gives step 5
     * a way to fetch the session's current values live instead.
     */
    public function summary(Request $request): JsonResponse
    {
        $wizard = session('wizard', []);
        $step1 = $wizard['step1'] ?? null;

        return response()->json([
            'step1' => $step1 ? Arr::except($step1, ['password_hash']) : null,
            'step3' => $wizard['step3'] ?? null,
            'step4' => $wizard['step4'] ?? null,
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

    public function photoPreview(Request $request)
    {
        $path = session('wizard.step2.avatar_tmp_path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response(Storage::disk('local')->get($path))->header('Content-Type', 'image/jpeg');
    }

    public function saveStep1(Step1Request $request): JsonResponse
    {
        $data = $request->validated();
        $google = session('wizard.google');

        $step1 = [
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'date_of_birth' => $data['date_of_birth'],
            'gender' => $data['gender'],
            'blood_group' => $data['blood_group'],
        ];

        if ($google) {
            $step1['email'] = $google['email'];
            $step1['via_google'] = true;
            $step1['google_id'] = $google['google_id'];
        } else {
            $step1['email'] = $data['email'];
            $step1['password_hash'] = Hash::make($data['password']);
            $step1['via_google'] = false;
        }

        session(['wizard.step1' => $step1]);
        $this->advanceFurthestStep(1);

        return response()->json(['success' => true, 'next_step' => 2]);
    }

    public function savePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $path = null;
        if ($request->hasFile('photo')) {
            $path = 'tmp-registration/'.$request->session()->getId().'.jpg';
            Storage::disk('local')->put($path, file_get_contents($request->file('photo')->getRealPath()));
        }

        session(['wizard.step2.avatar_tmp_path' => $path]);
        $this->advanceFurthestStep(2);

        return response()->json([
            'success' => true,
            'next_step' => 3,
            'preview_url' => $path ? route('register.photo-preview').'?t='.time() : null,
        ]);
    }

    public function saveStep3(Step3Request $request): JsonResponse
    {
        // Normalize blanks to null so a skipped address stores clean NULLs
        // in the DB rather than empty strings.
        $data = collect($request->validated())
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();

        session(['wizard.step3' => $data]);
        $this->advanceFurthestStep(3);

        return response()->json(['success' => true, 'next_step' => 4]);
    }

    public function saveStep4(Step4Request $request): JsonResponse
    {
        $data = $request->validated();
        $data['allergies'] = array_values(array_unique(array_filter($data['allergies'] ?? [])));
        $data['medicines'] = array_values(array_unique(array_filter($data['medicines'] ?? [])));

        session(['wizard.step4' => $data]);
        $this->advanceFurthestStep(4);

        return response()->json(['success' => true, 'next_step' => 5]);
    }

    /**
     * Final submit. Nothing touches the database until every step has passed
     * validation and this transaction commits — an abandoned wizard never
     * leaves a half-created account behind.
     */
    public function complete(Step5Request $request): JsonResponse
    {
        $wizard = session('wizard', []);

        if (! isset($wizard['step1'], $wizard['step2'], $wizard['step3'], $wizard['step4'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete all previous steps first.',
            ], 422);
        }

        $step1 = $wizard['step1'];
        $step2 = $wizard['step2'];
        $step3 = $wizard['step3'];
        $step4 = $wizard['step4'];
        $step5 = $request->validated();

        $avatarTmpPath = $step2['avatar_tmp_path'] ?? null;
        $hasPhoto = $avatarTmpPath && Storage::disk('local')->exists($avatarTmpPath);

        $user = DB::transaction(function () use ($step1, $step3, $step4, $step5, $avatarTmpPath, $hasPhoto, $request) {
            $permanentAvatarPath = null;
            if ($hasPhoto) {
                $permanentAvatarPath = 'avatars/'.Str::uuid().'.jpg';
                Storage::disk('public')->put($permanentAvatarPath, Storage::disk('local')->get($avatarTmpPath));
            }

            $user = User::create([
                'name' => $step1['full_name'],
                'email' => $step1['email'],
                'password' => $step1['password_hash'] ?? Str::password(32),
                'google_id' => $step1['google_id'] ?? null,
                'phone' => $step1['phone'],
                'avatar_path' => $permanentAvatarPath,
                'email_verified_at' => ($step1['via_google'] ?? false) ? now() : null,
            ]);

            $familyMember = FamilyMember::create([
                'primary_account_id' => $user->id,
                'linked_user_id' => $user->id,
                'relation' => 'self',
                'full_name' => $step1['full_name'],
                'date_of_birth' => $step1['date_of_birth'],
                'gender' => $step1['gender'],
                'blood_group' => $step1['blood_group'],
                'height_cm' => $step4['height_cm'],
                'weight_kg' => $step4['weight_kg'],
                'photo_path' => $permanentAvatarPath,
                'address_line1' => $step3['address_line1'] ?? null,
                'address_line2' => $step3['address_line2'] ?? null,
                'city' => $step3['city'] ?? null,
                'state' => $step3['state'] ?? null,
                'pincode' => $step3['pincode'] ?? null,
                'country' => $step3['country'] ?? null,
                'emergency_contact_name' => $step5['emergency_contact_name'],
                'emergency_contact_phone' => $step5['emergency_contact_phone'],
                'emergency_contact_relation' => $step5['emergency_contact_relation'],
                'access_type' => 'linked',
                'status' => 'active',
            ]);

            BmiLog::create([
                'family_member_id' => $familyMember->id,
                'height_cm' => $step4['height_cm'],
                'weight_kg' => $step4['weight_kg'],
                'recorded_date' => now()->toDateString(),
                'source' => 'manual',
            ]);

            foreach ($step4['allergies'] ?? [] as $allergen) {
                Allergy::create([
                    'family_member_id' => $familyMember->id,
                    'allergen_name' => $allergen,
                    'severity' => 'mild',
                ]);
            }

            foreach ($step4['medicines'] ?? [] as $medicineName) {
                Medication::create([
                    'family_member_id' => $familyMember->id,
                    'medicine_name' => $medicineName,
                    'active' => true,
                ]);
            }

            foreach (['account_creation', 'upload', 'ai_processing'] as $consentType) {
                Consent::grant($user, $consentType, $request);
            }

            IdCard::generateCard($familyMember);

            AuditLog::record('account_creation', 'User', $user->id, $user->id, $familyMember->id);

            return $user;
        });

        if ($avatarTmpPath) {
            Storage::disk('local')->delete($avatarTmpPath);
        }
        $request->session()->forget('wizard');

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->flash('just_registered', $user->name);

        return response()->json(['success' => true, 'redirect' => route('dashboard')]);
    }

    private function advanceFurthestStep(int $completedStep): void
    {
        $current = session('wizard.furthest_step', 1);
        session(['wizard.furthest_step' => max($current, min($completedStep + 1, self::TOTAL_STEPS))]);
    }
}
