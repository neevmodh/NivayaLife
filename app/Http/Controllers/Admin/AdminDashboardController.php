<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiJob;
use App\Models\AuditLog;
use App\Models\FamilyMember;
use App\Models\PageView;
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
    private const RANGE_DAYS = ['7' => 7, '30' => 30, '90' => 90, '365' => 365];

    public function index(Request $request): View
    {
        $range = $request->query('range', '30');
        $days = self::RANGE_DAYS[$range] ?? null; // null = all-time

        $since = $days
            ? now()->subDays($days - 1)->startOfDay()
            : (User::min('created_at') ? User::min('created_at')->copy()->startOfDay() : now()->startOfDay());

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

        $bloodGroupDistribution = FamilyMember::query()
            ->whereNotNull('blood_group')
            ->where('blood_group', '!=', 'Unknown')
            ->selectRaw('blood_group, count(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group');

        $landingViewsInRange = PageView::where('path', '/')->where('created_at', '>=', $since);
        $pageViewsInRange = (clone $landingViewsInRange)->count();
        $uniqueVisitorsInRange = (clone $landingViewsInRange)->distinct('ip_address')->count('ip_address');
        $signupsInRange = User::where('created_at', '>=', $since)->count();

        return view('admin.dashboard', [
            'range' => $range,
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
            'signupSeries' => $this->dailySeries(User::query(), $since),
            'reportSeries' => $this->dailySeries(Report::query(), $since, 'uploaded_at'),
            'bloodGroupDistribution' => $bloodGroupDistribution,
            'ageBuckets' => $this->ageBuckets(),
            'pageViewsTotal' => PageView::where('path', '/')->count(),
            'pageViewsToday' => PageView::where('path', '/')->whereDate('created_at', now())->count(),
            'pageViewsInRange' => $pageViewsInRange,
            'uniqueVisitorsInRange' => $uniqueVisitorsInRange,
            'pageViewSeries' => $this->dailySeries(PageView::where('path', '/'), $since),
            'pageViewsByHour' => $this->hourlyDistribution($since),
            'topReferrers' => $this->topReferrers($since),
            'conversionRate' => $pageViewsInRange > 0 ? round($signupsInRange / $pageViewsInRange * 100, 1) : null,
        ]);
    }

    /**
     * Zero-filled daily counts from $since to today, so a chart never shows a
     * misleading gap for a quiet day. Takes a query builder rather than a
     * model class so a caller can pre-filter it (e.g. PageView for one path).
     */
    private function dailySeries($query, \Illuminate\Support\Carbon $since, string $dateColumn = 'created_at'): array
    {
        $raw = (clone $query)
            ->where($dateColumn, '>=', $since)
            ->selectRaw("DATE({$dateColumn}) as day, count(*) as total")
            ->groupBy('day')
            ->pluck('total', 'day');

        $totalDays = max(1, (int) $since->diffInDays(now()) + 1);

        return collect(range(0, $totalDays - 1))
            ->map(function ($i) use ($since, $raw) {
                $date = $since->copy()->addDays($i)->format('Y-m-d');

                return ['date' => $date, 'count' => (int) ($raw[$date] ?? 0)];
            })
            ->all();
    }

    /** Zero-filled 0-23 hour-of-day counts for landing-page views, so the chart shows every hour even if some had none. */
    private function hourlyDistribution(\Illuminate\Support\Carbon $since): array
    {
        $raw = PageView::where('path', '/')
            ->where('created_at', '>=', $since)
            ->selectRaw('HOUR(created_at) as hour, count(*) as total')
            ->groupBy('hour')
            ->pluck('total', 'hour');

        return collect(range(0, 23))->map(fn ($hour) => (int) ($raw[$hour] ?? 0))->all();
    }

    /** Grouped by host rather than full URL, since two full referer URLs from the same site are the same "source" for this purpose. */
    private function topReferrers(\Illuminate\Support\Carbon $since): array
    {
        $raw = PageView::where('path', '/')
            ->where('created_at', '>=', $since)
            ->whereNotNull('referer')
            ->selectRaw('referer, count(*) as total')
            ->groupBy('referer')
            ->orderByDesc('total')
            ->limit(50)
            ->pluck('total', 'referer');

        $hostCounts = [];
        foreach ($raw as $referer => $count) {
            $host = parse_url($referer, PHP_URL_HOST) ?: $referer;
            $hostCounts[$host] = ($hostCounts[$host] ?? 0) + $count;
        }
        arsort($hostCounts);

        return array_slice($hostCounts, 0, 8, true);
    }

    /** Computed from the age() accessor, not a DB column, so this is done in PHP rather than SQL. */
    private function ageBuckets(): array
    {
        $buckets = ['0-12' => [0, 12], '13-19' => [13, 19], '20-35' => [20, 35], '36-50' => [36, 50], '51-65' => [51, 65], '66+' => [66, 200]];

        $ages = FamilyMember::query()->whereNotNull('date_of_birth')->get(['date_of_birth'])->map->age();

        return collect($buckets)
            ->map(fn ($range, $label) => $ages->filter(fn ($age) => $age >= $range[0] && $age <= $range[1])->count())
            ->all();
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

    /**
     * Paginated row browser for a single table, with a global search box and
     * sortable columns. Create/edit/delete links only render when the table
     * isn't a system/internal one — see AdminRecordController.
     */
    public function table(Request $request, string $table): View
    {
        [$query, $meta] = $this->buildTableQuery($request, $table);

        $rows = (clone $query)->orderBy($meta['sortColumn'], $meta['sortDir'])->paginate(25)->withQueryString();

        return view('admin.tables.show', [
            'table' => $table,
            'rows' => $rows,
            'isEditable' => ! in_array($table, AdminRecordController::SYSTEM_TABLES),
            ...$meta,
        ]);
    }

    /**
     * CSV of every row matching the current search/sort (not just the
     * current page) — same sensitive-column masking as the on-screen view,
     * since a downloaded file is easier to lose track of than a browser tab.
     */
    public function export(Request $request, string $table): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        [$query, $meta] = $this->buildTableQuery($request, $table);

        $sensitivePattern = '/password|secret|token|recovery_codes/i';
        $columns = $meta['columns'];

        return response()->streamDownload(function () use ($query, $meta, $columns, $sensitivePattern) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            (clone $query)->orderBy($meta['sortColumn'], $meta['sortDir'])
                ->chunk(500, function ($rows) use ($handle, $columns, $sensitivePattern) {
                    foreach ($rows as $row) {
                        fputcsv($handle, collect($columns)->map(function ($column) use ($row, $sensitivePattern) {
                            $value = $row->$column ?? null;

                            return preg_match($sensitivePattern, $column) && $value !== null ? '••••••••' : $value;
                        })->all());
                    }
                });

            fclose($handle);
        }, "{$table}.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * Shared by table() and export(): validates the table name, resolves
     * sortable/searchable columns from the schema, and applies the current
     * search term. Returns [query builder, metadata array for the view].
     */
    private function buildTableQuery(Request $request, string $table): array
    {
        // The table name reaches the query builder only after being checked
        // against the schema's own real table list — never interpolated
        // from the request unvalidated.
        abort_unless(in_array($table, Schema::getTableListing()), 404);

        $columnMeta = Schema::getColumns($table);
        $columns = array_column($columnMeta, 'name');
        $primaryKey = in_array('id', $columns) ? 'id' : $columns[0];

        $sortColumn = in_array($request->query('sort'), $columns, true) ? $request->query('sort') : $primaryKey;
        $sortDir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $searchableColumns = collect($columnMeta)
            ->filter(fn ($c) => in_array($c['type_name'], ['varchar', 'char', 'text', 'longtext', 'mediumtext', 'enum']))
            ->pluck('name')
            ->all();

        $q = trim((string) $request->query('q', ''));

        $query = DB::table($table);

        if ($q !== '' && $searchableColumns !== []) {
            $query->where(function ($sub) use ($searchableColumns, $q) {
                foreach ($searchableColumns as $column) {
                    $sub->orWhere($column, 'like', "%{$q}%");
                }
            });
        }

        return [$query, [
            'columns' => $columns,
            'primaryKey' => $primaryKey,
            'sortColumn' => $sortColumn,
            'sortDir' => $sortDir,
            'q' => $q,
            'searchableColumns' => $searchableColumns,
        ]];
    }
}
