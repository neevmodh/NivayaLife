<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiJob;
use App\Models\AuditLog;
use App\Models\FamilyMember;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only operational overview — gated by the same account login as
 * everything else (see EnsureUserIsAdmin), not a separate credential. No
 * route here ever writes to the database: that boundary is deliberate, since
 * this is meant to answer "what's going on" safely, not to double as a data
 * editor.
 */
class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $reportsByType = Report::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $reportsByOcrStatus = Report::query()
            ->selectRaw('ocr_status, count(*) as total')
            ->groupBy('ocr_status')
            ->pluck('total', 'ocr_status');

        $aiJobsByStatus = AiJob::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $familyMembersByAccessType = FamilyMember::query()
            ->selectRaw('access_type, count(*) as total')
            ->groupBy('access_type')
            ->pluck('total', 'access_type');

        return view('admin.dashboard', [
            'userCount' => User::count(),
            'verifiedUserCount' => User::whereNotNull('email_verified_at')->count(),
            'newUsersLast7Days' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'familyMemberCount' => FamilyMember::count(),
            'familyMembersByAccessType' => $familyMembersByAccessType,
            'reportCount' => Report::count(),
            'reportsByType' => $reportsByType,
            'reportsByOcrStatus' => $reportsByOcrStatus,
            'totalStorageBytes' => Report::sum('file_size'),
            'aiJobsByStatus' => $aiJobsByStatus,
            'failedJobCount' => DB::table('failed_jobs')->count(),
            'pendingJobCount' => DB::table('jobs')->count(),
            'recentUsers' => User::latest()->take(10)->get(['id', 'name', 'email', 'created_at']),
            'recentAuditLog' => AuditLog::with('user:id,name,email')->latest()->take(20)->get(),
        ]);
    }

    /** Every real table in the schema, with a live row count, nothing hardcoded. */
    public function tables(): View
    {
        $tables = collect(Schema::getTableListing())
            ->reject(fn ($table) => in_array($table, ['migrations', 'sessions', 'cache', 'cache_locks', 'job_batches']))
            ->map(fn ($table) => ['name' => $table, 'count' => DB::table($table)->count()])
            ->sortBy('name')
            ->values();

        return view('admin.tables.index', ['tables' => $tables]);
    }

    /** Paginated, read-only row browser for a single table — no edit/delete/create path exists anywhere in this controller. */
    public function table(Request $request, string $table): View
    {
        // The table name reaches the query builder only after being checked
        // against the schema's own real table list — never interpolated
        // from the request unvalidated.
        abort_unless(in_array($table, Schema::getTableListing()), 404);

        $columns = Schema::getColumnListing($table);
        $orderColumn = in_array('id', $columns) ? 'id' : $columns[0];

        $rows = DB::table($table)->orderByDesc($orderColumn)->paginate(25)->withQueryString();

        return view('admin.tables.show', [
            'table' => $table,
            'columns' => $columns,
            'rows' => $rows,
        ]);
    }
}
