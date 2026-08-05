<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\FamilyMember;
use App\Models\LoginLog;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read-only drill-down on a single account, and the failed-jobs queue.
 *
 * The dashboard could only ever say "12 failed jobs" with no way to see or
 * act on them, and there was no way to answer "what is going on with this
 * one user" without querying the database by hand.
 */
class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->withCount('familyMembers')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'search' => $search]);
    }

    public function show(User $user): View
    {
        $memberIds = FamilyMember::where('primary_account_id', $user->id)
            ->orWhere('linked_user_id', $user->id)
            ->pluck('id');

        $reports = Report::whereIn('family_member_id', $memberIds);

        return view('admin.users.show', [
            'user' => $user,
            'members' => FamilyMember::whereIn('id', $memberIds)->orderByRaw("relation = 'self' desc")->get(),
            'reportCount' => (clone $reports)->count(),
            'storageBytes' => (clone $reports)->sum('file_size'),
            'reportsByStatus' => (clone $reports)->selectRaw('ocr_status, count(*) as total')->groupBy('ocr_status')->pluck('total', 'ocr_status'),
            'recentReports' => (clone $reports)->latest('uploaded_at')->limit(8)->get(),
            'chatStats' => ChatMessage::where('asked_by_user_id', $user->id)
                ->selectRaw('COUNT(*) as messages, COALESCE(SUM(input_tokens + output_tokens),0) as tokens')
                ->first(),
            'recentLogins' => LoginLog::where('user_id', $user->id)->latest()->limit(8)->get(),
            'recentActivity' => AuditLog::where('user_id', $user->id)->latest()->limit(12)->get(),
        ]);
    }

    /** The failed queue, with the payload decoded far enough to be readable. */
    public function failedJobs(): View
    {
        $jobs = DB::table('failed_jobs')->orderByDesc('failed_at')->limit(50)->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);

                return (object) [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'queue' => $job->queue,
                    'name' => $payload['displayName'] ?? 'Unknown job',
                    // The stack trace is long; the first line is the part that
                    // actually says what went wrong.
                    'reason' => trim(strtok((string) $job->exception, "\n") ?: 'Unknown error'),
                    'exception' => $job->exception,
                    'failed_at' => $job->failed_at,
                ];
            });

        return view('admin.failed-jobs', ['jobs' => $jobs, 'total' => DB::table('failed_jobs')->count()]);
    }

    public function retryFailedJob(Request $request, string $uuid): RedirectResponse
    {
        $exists = DB::table('failed_jobs')->where('uuid', $uuid)->exists();

        if (! $exists) {
            return back()->with('admin_error', 'That job is no longer in the failed queue.');
        }

        // Delegates to Laravel's own retry command so the job is re-queued
        // exactly as the framework would do it.
        \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => [$uuid]]);
        AuditLog::record('failed_job_retried', 'FailedJob', 0);

        return back()->with('admin_status', 'Job re-queued.');
    }

    public function deleteFailedJob(Request $request, string $uuid): RedirectResponse
    {
        $deleted = DB::table('failed_jobs')->where('uuid', $uuid)->delete();

        if ($deleted === 0) {
            return back()->with('admin_error', 'That job is no longer in the failed queue.');
        }

        AuditLog::record('failed_job_deleted', 'FailedJob', 0);

        return back()->with('admin_status', 'Job removed from the failed queue.');
    }
}
