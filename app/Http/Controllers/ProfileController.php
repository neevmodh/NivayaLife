<?php

namespace App\Http\Controllers;

use App\Models\Allergy;
use App\Models\ArchivedAccount;
use App\Models\BmiLog;
use App\Models\FamilyMember;
use App\Models\Medication;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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
    /**
     * Shared by two routes: /profile (own profile, no route param) and
     * /family/{familyMember}/edit (a dependent's profile, primary account
     * only). Same view, same tab partials — reuse.
     */
    public function edit(Request $request, ?FamilyMember $familyMember = null): View
    {
        $user = $request->user();
        $member = $this->ownedMember($request, $familyMember);

        $archivedMembers = $familyMember ? collect() : FamilyMember::onlyTrashed()
            ->where('primary_account_id', $user->id)
            ->orderByDesc('deleted_at')
            ->get();

        return view('profile.edit', [
            'user' => $user,
            'member' => $member,
            'archivedMembers' => $archivedMembers,
            'isDependentEdit' => $familyMember !== null,
        ]);
    }

    public function updateBasicInfo(Request $request, ?FamilyMember $familyMember = null): JsonResponse
    {
        $member = $this->ownedMember($request, $familyMember);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female,other,prefer_not_to_say'],
            'blood_group' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-,Unknown'],
        ]);

        $member->update($validated);

        if (! $familyMember) {
            $request->user()->update(['name' => $validated['full_name']]);
        }

        return response()->json(['success' => true]);
    }

    public function updatePhoto(Request $request, ?FamilyMember $familyMember = null): JsonResponse
    {
        $member = $this->ownedMember($request, $familyMember);

        $request->validate(['photo' => ['required', 'image', 'max:5120']]);

        $path = 'avatars/'.Str::uuid().'.jpg';
        Storage::disk('public')->put($path, file_get_contents($request->file('photo')->getRealPath()));

        $oldPath = $member->photo_path;
        $member->update(['photo_path' => $path]);

        if (! $familyMember) {
            $request->user()->update(['avatar_path' => $path]);
        }

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json(['success' => true, 'photo_url' => Storage::url($path)]);
    }

    public function updateAddress(Request $request, ?FamilyMember $familyMember = null): JsonResponse
    {
        $member = $this->ownedMember($request, $familyMember);

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
    public function updateHealth(Request $request, ?FamilyMember $familyMember = null): JsonResponse
    {
        $member = $this->ownedMember($request, $familyMember);

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

    public function updateEmergencyContact(Request $request, ?FamilyMember $familyMember = null): JsonResponse
    {
        $member = $this->ownedMember($request, $familyMember);

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
        // A Google-only account's password is a random string nobody knows
        // (see PasswordController) — requiring it here would make deletion
        // permanently impossible for those accounts. The active,
        // already-authenticated session is the identity proof for them
        // instead; everyone else still has to type their real password.
        $rules = ['confirmation' => ['required', Rule::in(['DELETE'])]];

        if ($request->user()->has_password) {
            $rules['password'] = ['required', 'current_password'];
        }

        $request->validateWithBag('userDeletion', $rules, [
            'confirmation.in' => 'Please type DELETE to confirm.',
        ]);

        $user = $request->user();

        // Auth::logout() clears the remember_token via $user->save() — it
        // must run before the row is deleted, or Eloquent (seeing
        // exists=false post-delete) would re-INSERT the just-deleted user.
        Auth::logout();

        DB::transaction(function () use ($user) {
            $familyMembers = FamilyMember::withTrashed()->where('primary_account_id', $user->id)->get();

            $this->archiveAccount($user, $familyMembers);

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

    /**
     * Writes a full snapshot of everything this account owns into
     * archived_accounts before any of it is actually deleted below — the
     * account page tells the user their data is retained for records
     * rather than truly gone, so this has to run first (and inside the
     * same transaction the deletion runs in) or that claim would be false.
     * Uploaded report files are moved, not copied, into a per-user archive
     * directory — a JSON column can't hold binary data, and the live
     * report row is about to be force-deleted anyway. This is intentionally
     * not linked by foreign key to anything live: a later signup with the
     * same email must never automatically reattach to it.
     */
    /** @param  Collection<int, FamilyMember>  $familyMembers */
    private function archiveAccount(User $user, Collection $familyMembers): void
    {
        $snapshot = [];
        $archivedFiles = [];

        foreach ($familyMembers as $member) {
            $reports = $member->reports()->withTrashed()->get()->map(function ($report) use (&$archivedFiles, $user) {
                $data = $report->toArray();

                if ($report->file_path && Storage::disk('local')->exists($report->file_path)) {
                    $archivedPath = 'archived-reports/'.$user->id.'/'.basename($report->file_path);
                    Storage::disk('local')->move($report->file_path, $archivedPath);

                    $archivedFiles[] = [
                        'report_id' => $report->id,
                        'original_filename' => $report->original_filename,
                        'archived_path' => $archivedPath,
                    ];
                    $data['archived_file_path'] = $archivedPath;
                }

                return $data;
            });

            $snapshot[] = [
                'family_member' => $member->toArray(),
                'allergies' => $member->allergies()->get()->toArray(),
                'chronic_conditions' => $member->chronicConditions()->get()->toArray(),
                'vaccinations' => $member->vaccinations()->get()->toArray(),
                'health_metrics' => $member->healthMetrics()->get()->toArray(),
                'bmi_logs' => $member->bmiLogs()->get()->toArray(),
                'medications' => $member->medications()->with('medicationLogs')->get()->toArray(),
                'doctors' => $member->doctors()->get()->toArray(),
                'insurance_policies' => $member->insurancePolicies()->get()->toArray(),
                'id_card_history' => $member->idCardHistory()->get()->toArray(),
                'reports' => $reports->toArray(),
            ];
        }

        ArchivedAccount::create([
            'original_user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'phone' => $user->phone,
            'password' => $user->password,
            'google_id' => $user->google_id,
            'was_admin' => (bool) $user->is_admin,
            'original_created_at' => $user->created_at,
            'data' => $snapshot,
            'archived_files' => $archivedFiles,
            'archived_at' => now(),
        ]);
    }

    private function ownedMember(Request $request, ?FamilyMember $familyMember = null): FamilyMember
    {
        if ($familyMember) {
            abort_unless($familyMember->canBeEditedBy($request->user()), 403);

            return $familyMember;
        }

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
