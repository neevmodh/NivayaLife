<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveFamilyMember;
use App\Models\FamilyMember;
use App\Models\Vaccination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VaccinationController extends Controller
{
    use ResolvesActiveFamilyMember;

    public function index(Request $request): View
    {
        $user = $request->user();
        $active = $this->resolveActiveFamilyMember($request, $user);
        abort_unless($active->hasGrantedAccessTo($user), 403);

        $vaccinations = Vaccination::where('family_member_id', $active->id)
            ->orderByDesc('date_administered')
            ->get();

        return view('vaccinations.index', [
            'active' => $active,
            'vaccinations' => $vaccinations,
            'canEdit' => $active->canBeEditedBy($user),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $active = $this->resolveActiveFamilyMember($request, $user);
        abort_unless($active->canBeEditedBy($user), 403);

        return view('vaccinations.form', [
            'active' => $active,
            'vaccination' => new Vaccination,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $familyMember = FamilyMember::findOrFail($validated['family_member_id']);
        abort_unless($familyMember->canBeEditedBy($request->user()), 403);

        Vaccination::create($validated);

        return redirect()->route('vaccinations.index', ['member' => $familyMember->id])->with('status', 'Vaccination added.');
    }

    public function edit(Request $request, Vaccination $vaccination): View
    {
        abort_unless($vaccination->familyMember->canBeEditedBy($request->user()), 403);

        return view('vaccinations.form', [
            'active' => $vaccination->familyMember,
            'vaccination' => $vaccination,
        ]);
    }

    public function update(Request $request, Vaccination $vaccination): RedirectResponse
    {
        abort_unless($vaccination->familyMember->canBeEditedBy($request->user()), 403);

        $vaccination->update($this->validated($request));

        return redirect()->route('vaccinations.index', ['member' => $vaccination->family_member_id])->with('status', 'Vaccination updated.');
    }

    public function destroy(Request $request, Vaccination $vaccination): RedirectResponse
    {
        abort_unless($vaccination->familyMember->canBeEditedBy($request->user()), 403);

        $familyMemberId = $vaccination->family_member_id;
        $vaccination->delete();

        return redirect()->route('vaccinations.index', ['member' => $familyMemberId])->with('status', 'Vaccination removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'vaccine_name' => ['required', 'string', 'max:255'],
            'dose_number' => ['required', 'integer', 'min:1'],
            'date_administered' => ['required', 'date', 'before_or_equal:tomorrow'],
            'next_due_date' => ['nullable', 'date', 'after:date_administered'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
