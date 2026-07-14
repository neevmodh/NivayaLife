<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveFamilyMember;
use App\Models\AuditLog;
use App\Models\FamilyMember;
use App\Models\Report;
use App\Models\Share;
use App\Services\Qr\QrCodeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Owner-side share management. The schema gives shares exactly two real
 * bundle shapes — a single report (report_id set) or the family member's
 * full report history (report_id null, family_member_id set) — so the
 * create form offers exactly those two choices honestly, rather than a
 * fake "pick any combination of reports" picker with no schema behind it.
 */
class ShareController extends Controller
{
    use ResolvesActiveFamilyMember;

    public function create(Request $request): View
    {
        $user = $request->user();

        $report = $request->integer('report') ? Report::findOrFail($request->integer('report')) : null;
        $familyMember = $report
            ? $report->familyMember
            : ($request->integer('family_member_id')
                ? FamilyMember::findOrFail($request->integer('family_member_id'))
                : $this->resolveActiveFamilyMember($request, $user));

        abort_unless($familyMember->canBeEditedBy($user), 403);

        $reportCount = $familyMember->reports()->where('is_archived', false)->count();

        return view('shares.create', [
            'familyMember' => $familyMember,
            'report' => $report,
            'reportCount' => $reportCount,
            'defaultAccessType' => $report ? 'single_report' : 'full_summary',
        ]);
    }

    public function store(Request $request): View
    {
        $user = $request->user();

        $validated = $request->validate([
            'access_type' => ['required', 'in:single_report,full_summary'],
            'report_id' => ['required_if:access_type,single_report', 'nullable', 'integer', 'exists:reports,id'],
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'shared_with_label' => ['nullable', 'string', 'max:255'],
            'expiry_option' => ['required', 'in:24h,48h,7d,custom'],
            'custom_expires_at' => ['required_if:expiry_option,custom', 'nullable', 'date', 'after:now'],
            'pin' => ['nullable', 'digits:4'],
            'is_one_time' => ['nullable', 'boolean'],
        ]);

        $familyMember = FamilyMember::findOrFail($validated['family_member_id']);
        abort_unless($familyMember->canBeEditedBy($user), 403);

        $report = null;
        if ($validated['access_type'] === 'single_report') {
            $report = Report::findOrFail($validated['report_id']);
            abort_unless($report->family_member_id === $familyMember->id, 403);
        }

        $expiresAt = match ($validated['expiry_option']) {
            '24h' => now()->addDay(),
            '48h' => now()->addDays(2),
            '7d' => now()->addDays(7),
            'custom' => Carbon::parse($validated['custom_expires_at']),
        };

        $share = new Share([
            'report_id' => $report?->id,
            'family_member_id' => $familyMember->id,
            'access_type' => $validated['access_type'],
            'shared_with_label' => $validated['shared_with_label'] ?: null,
            'expires_at' => $expiresAt,
            'is_one_time' => $request->boolean('is_one_time'),
        ]);

        if (! empty($validated['pin'])) {
            $share->setPin($validated['pin']);
        }

        $share->save();

        AuditLog::record('share_created', 'Share', $share->id, $user->id, $familyMember->id);

        $publicUrl = route('share.public.show', $share->token);
        $qrService = app(QrCodeService::class);

        return view('shares.success', [
            'share' => $share,
            'familyMember' => $familyMember,
            'publicUrl' => $publicUrl,
            'qrDataUri' => $qrService->dataUri($publicUrl),
        ]);
    }

    public function history(Request $request): View
    {
        $user = $request->user();
        $active = $this->resolveActiveFamilyMember($request, $user);
        abort_unless($active->hasGrantedAccessTo($user), 403);

        $shares = Share::where('family_member_id', $active->id)
            ->with('report')
            ->latest()
            ->get();

        return view('shares.history', [
            'active' => $active,
            'shares' => $shares,
        ]);
    }

    public function revoke(Request $request, Share $share): RedirectResponse
    {
        $user = $request->user();
        abort_unless($share->familyMember->canBeEditedBy($user), 403);

        $share->update(['revoked_at' => now()]);

        AuditLog::record('share_revoked', 'Share', $share->id, $user->id, $share->family_member_id);

        return back()->with('status', 'share-revoked');
    }
}
