<?php

namespace App\Http\Controllers;

use App\Models\Allergy;
use App\Models\BmiLog;
use App\Models\FamilyMember;
use App\Models\Medication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $member = $user->ensureLinkedFamilyMember();

        return view('profile.edit', [
            'user' => $user,
            'member' => $member,
        ]);
    }

    public function updateBasicInfo(Request $request): JsonResponse
    {
        $member = $this->ownedMember($request);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female,other,prefer_not_to_say'],
            'blood_group' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-,Unknown'],
        ]);

        $member->update($validated);
        $request->user()->update(['name' => $validated['full_name']]);

        return response()->json(['success' => true]);
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $member = $this->ownedMember($request);

        $request->validate(['photo' => ['required', 'image', 'max:5120']]);

        $path = 'avatars/'.Str::uuid().'.jpg';
        Storage::disk('public')->put($path, file_get_contents($request->file('photo')->getRealPath()));

        $oldPath = $member->photo_path;
        $member->update(['photo_path' => $path]);
        $request->user()->update(['avatar_path' => $path]);

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json(['success' => true, 'photo_url' => Storage::url($path)]);
    }

    public function updateAddress(Request $request): JsonResponse
    {
        $member = $this->ownedMember($request);

        $pincodeRule = $request->input('country') === 'India' ? ['required', 'digits:6'] : ['required', 'string', 'max:12'];

        $validated = $request->validate([
            'country' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'pincode' => $pincodeRule,
        ]);

        $member->update($validated);

        return response()->json(['success' => true]);
    }

    /**
     * Editing height/weight here always creates a NEW bmi_logs row rather
     * than overwriting the family member's last one, so the trend history
     * shown on the dashboard stays intact.
     */
    public function updateHealth(Request $request): JsonResponse
    {
        $member = $this->ownedMember($request);

        $validated = $request->validate([
            'height_cm' => ['required', 'numeric', 'min:30', 'max:280'],
            'weight_kg' => ['required', 'numeric', 'min:2', 'max:400'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:255'],
            'medicines' => ['nullable', 'array'],
            'medicines.*' => ['string', 'max:255'],
        ]);

        $member->update([
            'height_cm' => $validated['height_cm'],
            'weight_kg' => $validated['weight_kg'],
        ]);

        BmiLog::create([
            'family_member_id' => $member->id,
            'height_cm' => $validated['height_cm'],
            'weight_kg' => $validated['weight_kg'],
            'recorded_date' => now()->toDateString(),
            'source' => 'manual',
        ]);

        $this->syncAllergies($member, $validated['allergies'] ?? []);
        $this->syncMedicines($member, $validated['medicines'] ?? []);

        return response()->json(['success' => true]);
    }

    public function updateEmergencyContact(Request $request): JsonResponse
    {
        $member = $this->ownedMember($request);

        $validated = $request->validate([
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'digits:10'],
            'emergency_contact_relation' => ['required', 'string', 'max:255'],
        ]);

        $member->update($validated);

        return response()->json(['success' => true]);
    }

    public function updateTheme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme_preference' => ['required', 'in:light,dark'],
        ]);

        $request->user()->update($validated);

        return response()->json(['success' => true]);
    }

    /**
     * bmi_logs / allergies / medications / reports / etc. deliberately use
     * ON DELETE RESTRICT against family_members (see the schema report) so
     * routine record edits can never silently destroy medical history. A
     * full account deletion is an explicit, typed-confirmation exception to
     * that — so every dependent row is removed here first, in the correct
     * order, before the family_member (and then the user) can be dropped.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', Rule::in(['DELETE'])],
        ], [
            'confirmation.in' => 'Please type DELETE to confirm.',
        ]);

        $user = $request->user();

        // Auth::logout() clears the remember_token via $user->save() — it
        // must run before the row is deleted, or Eloquent (seeing
        // exists=false post-delete) would re-INSERT the just-deleted user.
        Auth::logout();

        DB::transaction(function () use ($user) {
            $familyMembers = FamilyMember::withTrashed()->where('primary_account_id', $user->id)->get();

            foreach ($familyMembers as $member) {
                $member->allergies()->delete();
                $member->chronicConditions()->delete();
                $member->vaccinations()->delete();
                $member->healthMetrics()->delete();
                $member->bmiLogs()->delete();
                $member->medications()->delete();
                $member->doctors()->delete();
                $member->insurancePolicies()->delete();
                $member->idCardHistory()->delete();
                $member->shares()->delete();
                $member->sharingPermissions()->delete();
                $member->invitations()->delete();
                $member->reports()->withTrashed()->get()->each->forceDelete();
                $member->forceDelete();
            }

            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function ownedMember(Request $request): FamilyMember
    {
        return $request->user()->ensureLinkedFamilyMember();
    }

    private function syncAllergies(FamilyMember $member, array $names): void
    {
        $names = array_values(array_unique(array_filter($names)));

        $member->allergies()->whereNotIn('allergen_name', $names)->delete();

        $existing = $member->allergies()->pluck('allergen_name')->all();

        foreach (array_diff($names, $existing) as $name) {
            Allergy::create([
                'family_member_id' => $member->id,
                'allergen_name' => $name,
                'severity' => 'mild',
            ]);
        }
    }

    private function syncMedicines(FamilyMember $member, array $names): void
    {
        $names = array_values(array_unique(array_filter($names)));

        $member->medications()->whereNotIn('medicine_name', $names)->delete();

        $existing = $member->medications()->pluck('medicine_name')->all();

        foreach (array_diff($names, $existing) as $name) {
            Medication::create([
                'family_member_id' => $member->id,
                'medicine_name' => $name,
                'active' => true,
            ]);
        }
    }
}
