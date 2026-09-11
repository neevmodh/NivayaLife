<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BmiLog;
use App\Models\FamilyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FamilyDependentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // The location-select widget leaves country blank until the user
        // actually picks one, so its presence is a direct "was a location
        // given" signal — no need to infer that from address_line1.
        $pincodeRule = match (true) {
            $request->input('country') === 'India' => ['required', 'digits:6'],
            filled($request->input('country')) => ['required', 'string', 'max:12'],
            default => ['nullable', 'string', 'max:12'],
        };

        $validated = $request->validate([
            'relation' => ['required', 'in:spouse,father,mother,son,daughter,grandfather,grandmother,other'],
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female,other,prefer_not_to_say'],
            'blood_group' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-,Unknown'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'country' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'pincode' => $pincodeRule,
            'height_cm' => ['required', 'numeric', 'min:30', 'max:280'],
            'weight_kg' => ['required', 'numeric', 'min:2', 'max:400'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'digits:10'],
            'emergency_contact_relation' => ['required', 'string', 'max:255'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = 'avatars/'.Str::uuid().'.jpg';
            Storage::disk('public')->put($photoPath, file_get_contents($request->file('photo')->getRealPath()));
        }

        DB::transaction(function () use ($user, $validated, $photoPath) {
            $familyMember = FamilyMember::create([
                'primary_account_id' => $user->id,
                'linked_user_id' => null,
                'relation' => $validated['relation'],
                'full_name' => $validated['full_name'],
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'blood_group' => $validated['blood_group'],
                'height_cm' => $validated['height_cm'],
                'weight_kg' => $validated['weight_kg'],
                'photo_path' => $photoPath,
                'address_line1' => ($validated['address_line1'] ?? null) ?: null,
                'address_line2' => ($validated['address_line2'] ?? null) ?: null,
                'city' => ($validated['city'] ?? null) ?: null,
                'state' => ($validated['state'] ?? null) ?: null,
                'pincode' => ($validated['pincode'] ?? null) ?: null,
                'country' => ($validated['country'] ?? null) ?: null,
                'emergency_contact_name' => $validated['emergency_contact_name'],
                'emergency_contact_phone' => $validated['emergency_contact_phone'],
                'emergency_contact_relation' => $validated['emergency_contact_relation'],
                'access_type' => 'dependent',
                'status' => 'active',
            ]);

            BmiLog::create([
                'family_member_id' => $familyMember->id,
                'height_cm' => $validated['height_cm'],
                'weight_kg' => $validated['weight_kg'],
                'recorded_date' => now()->toDateString(),
                'source' => 'manual',
            ]);

            AuditLog::record('dependent_added', 'FamilyMember', $familyMember->id, $user->id, $familyMember->id);
        });

        return response()->json(['success' => true, 'redirect' => route('family.index')]);
    }
}
