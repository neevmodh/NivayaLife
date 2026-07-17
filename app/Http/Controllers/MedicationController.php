<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveFamilyMember;
use App\Models\FamilyMember;
use App\Models\Medication;
use App\Models\MedicationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicationController extends Controller
{
    use ResolvesActiveFamilyMember;

    public function index(Request $request): View
    {
        $user = $request->user();
        $active = $this->resolveActiveFamilyMember($request, $user);
        abort_unless($active->hasGrantedAccessTo($user), 403);

        $medications = Medication::where('family_member_id', $active->id)
            ->orderByDesc('active')
            ->orderBy('medicine_name')
            ->get();

        return view('medications.index', [
            'active' => $active,
            'medications' => $medications,
            'canEdit' => $active->canBeEditedBy($user),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $active = $this->resolveActiveFamilyMember($request, $user);
        abort_unless($active->canBeEditedBy($user), 403);

        return view('medications.form', [
            'active' => $active,
            'medication' => new Medication,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $familyMember = FamilyMember::findOrFail($validated['family_member_id']);
        abort_unless($familyMember->canBeEditedBy($request->user()), 403);

        Medication::create($validated);

        return redirect()->route('medications.index', ['member' => $familyMember->id])->with('status', 'Medication added.');
    }

    public function edit(Request $request, Medication $medication): View
    {
        abort_unless($medication->familyMember->canBeEditedBy($request->user()), 403);

        return view('medications.form', [
            'active' => $medication->familyMember,
            'medication' => $medication,
        ]);
    }

    public function update(Request $request, Medication $medication): RedirectResponse
    {
        abort_unless($medication->familyMember->canBeEditedBy($request->user()), 403);

        $medication->update($this->validated($request));

        return redirect()->route('medications.index', ['member' => $medication->family_member_id])->with('status', 'Medication updated.');
    }

    public function destroy(Request $request, Medication $medication): RedirectResponse
    {
        abort_unless($medication->familyMember->canBeEditedBy($request->user()), 403);

        $familyMemberId = $medication->family_member_id;
        $medication->delete();

        return redirect()->route('medications.index', ['member' => $familyMemberId])->with('status', 'Medication removed.');
    }

    /**
     * Clicking a dose pill on the dashboard toggles it between taken and
     * pending — never 'missed', which is reserved for the scheduled sweep
     * once a pending slot's time has genuinely passed. Looked up (and
     * created if missing) by medication+time rather than a log ID, since a
     * medication added after today's log-generation command already ran
     * won't have a row yet — this works regardless of whether that
     * housekeeping has caught up.
     */
    public function toggleDose(Request $request, Medication $medication): JsonResponse
    {
        abort_unless($medication->familyMember->canBeEditedBy($request->user()), 403);

        $validated = $request->validate([
            'time' => ['required', 'date_format:H:i'],
        ]);

        $scheduledAt = today()->setTimeFromTimeString($validated['time']);

        $log = MedicationLog::firstOrCreate(
            ['medication_id' => $medication->id, 'scheduled_at' => $scheduledAt],
            ['status' => 'pending']
        );

        if ($log->status === 'taken') {
            $log->update(['status' => 'pending', 'taken_at' => null]);
        } else {
            $log->update(['status' => 'taken', 'taken_at' => now()]);
        }

        return response()->json(['success' => true, 'status' => $log->status]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'medicine_name' => ['required', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'frequency' => ['nullable', 'string', 'max:100'],
            'schedule_times' => ['nullable', 'array'],
            'schedule_times.*' => ['date_format:H:i'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'prescribing_doctor' => ['nullable', 'string', 'max:255'],
        ]);

        $data['active'] = $request->boolean('active');
        $data['reminder_enabled'] = $request->boolean('reminder_enabled');
        $data['schedule_times'] = array_values(array_unique(array_filter($data['schedule_times'] ?? [])));

        return $data;
    }
}
