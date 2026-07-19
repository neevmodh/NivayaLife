<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Structured create/update/delete for a single row at a time — deliberately
 * NOT a raw SQL console. Every write goes through the query builder with
 * bound parameters, every table name and column name is checked against the
 * schema's own introspection before use, and every mutation is recorded to
 * the audit log. Password/token/secret-shaped columns are excluded from the
 * form entirely (not just masked) so a hashed value can never be overwritten
 * with a plain-text one through this UI.
 */
class AdminRecordController extends Controller
{
    /** Internal/system tables no admin should hand-edit — queues, sessions, cache, framework bookkeeping. */
    public const SYSTEM_TABLES = [
        'migrations', 'sessions', 'cache', 'cache_locks', 'job_batches',
        'jobs', 'failed_jobs', 'password_reset_tokens',
    ];

    private const SENSITIVE_COLUMN_PATTERN = '/password|secret|token|recovery_codes/i';

    public function create(string $table): View
    {
        $this->authorizeTable($table);

        $columns = $this->editableColumns($table);

        return view('admin.tables.create', ['table' => $table, 'columns' => $columns]);
    }

    public function store(Request $request, string $table): RedirectResponse
    {
        $this->authorizeTable($table);

        $columns = $this->editableColumns($table);
        $data = $this->collectFormValues($request, $columns);

        if (in_array('created_at', Schema::getColumnListing($table))) {
            $data['created_at'] = now();
        }
        if (in_array('updated_at', Schema::getColumnListing($table))) {
            $data['updated_at'] = now();
        }

        try {
            $id = DB::table($table)->insertGetId($data);
        } catch (QueryException $e) {
            return back()->withInput()->with('admin_error', $this->friendlyError($e));
        }

        AuditLog::record('admin_row_created', $table, (int) $id);

        return redirect()->route('admin.tables.show', $table)->with('admin_status', "Row #{$id} created in {$table}.");
    }

    public function edit(string $table, string $id): View
    {
        $this->authorizeTable($table);

        $pk = $this->primaryKey($table);
        $row = DB::table($table)->where($pk, $id)->first();
        abort_unless($row, 404);

        $columns = $this->editableColumns($table);

        return view('admin.tables.edit', ['table' => $table, 'id' => $id, 'row' => $row, 'columns' => $columns]);
    }

    public function update(Request $request, string $table, string $id): RedirectResponse
    {
        $this->authorizeTable($table);

        $pk = $this->primaryKey($table);
        $exists = DB::table($table)->where($pk, $id)->exists();
        abort_unless($exists, 404);

        $columns = $this->editableColumns($table);
        $data = $this->collectFormValues($request, $columns);

        if (in_array('updated_at', Schema::getColumnListing($table))) {
            $data['updated_at'] = now();
        }

        try {
            DB::table($table)->where($pk, $id)->update($data);
        } catch (QueryException $e) {
            return back()->withInput()->with('admin_error', $this->friendlyError($e));
        }

        AuditLog::record('admin_row_updated', $table, (int) $id);

        return redirect()->route('admin.tables.show', $table)->with('admin_status', "Row #{$id} updated in {$table}.");
    }

    /**
     * Soft-deletes (sets deleted_at) when the table has that column, matching
     * how the rest of the app treats records like family members and reports
     * — "archived, not destroyed" — rather than silently hard-deleting
     * through a path that bypasses the app's own soft-delete convention.
     */
    public function destroy(string $table, string $id): RedirectResponse
    {
        $this->authorizeTable($table);

        $pk = $this->primaryKey($table);
        $columns = Schema::getColumnListing($table);

        try {
            if (in_array('deleted_at', $columns)) {
                DB::table($table)->where($pk, $id)->update(['deleted_at' => now()]);
            } else {
                DB::table($table)->where($pk, $id)->delete();
            }
        } catch (QueryException $e) {
            return back()->with('admin_error', $this->friendlyError($e));
        }

        AuditLog::record('admin_row_deleted', $table, (int) $id);

        return redirect()->route('admin.tables.show', $table)->with('admin_status', "Row #{$id} deleted from {$table}.");
    }

    private function authorizeTable(string $table): void
    {
        abort_unless(in_array($table, Schema::getTableListing()), 404);
        abort_if(in_array($table, self::SYSTEM_TABLES), 403, 'This table is not editable through the admin panel.');
    }

    private function primaryKey(string $table): string
    {
        $columns = Schema::getColumnListing($table);

        return in_array('id', $columns) ? 'id' : $columns[0];
    }

    /**
     * Every real column minus id/timestamps (server-managed) and anything
     * password/secret/token-shaped, enriched with type info so the view can
     * render an enum as a dropdown, a long-text column as a textarea, etc.
     * instead of a single generic text input for everything.
     */
    private function editableColumns(string $table): array
    {
        return collect(Schema::getColumns($table))
            ->reject(fn ($column) => in_array($column['name'], ['id', 'created_at', 'updated_at', 'deleted_at']))
            ->reject(fn ($column) => preg_match(self::SENSITIVE_COLUMN_PATTERN, $column['name']))
            ->map(function ($column) {
                $enumOptions = [];
                if ($column['type_name'] === 'enum' && preg_match_all("/'([^']*)'/", $column['type'], $matches)) {
                    $enumOptions = $matches[1];
                }

                return [
                    'name' => $column['name'],
                    'type_name' => $column['type_name'],
                    'nullable' => (bool) $column['nullable'],
                    'enum_options' => $enumOptions,
                    'is_boolean' => $column['type_name'] === 'tinyint' && str_contains($column['type'], '(1)'),
                ];
            })
            ->values()
            ->all();
    }

    private function collectFormValues(Request $request, array $columns): array
    {
        // Boolean columns are always required (rendered as a Yes/No select
        // with no blank option) — everything else follows the schema's own
        // nullability, so a genuinely required column gets a clear
        // validation message instead of a raw SQL "column cannot be null" error.
        $rules = collect($columns)->mapWithKeys(fn ($column) => [
            $column['name'] => [$column['is_boolean'] || $column['nullable'] ? 'nullable' : 'required'],
        ])->all();

        $validated = $request->validate($rules);

        return collect($columns)
            ->mapWithKeys(function ($column) use ($validated) {
                $name = $column['name'];
                $value = $validated[$name] ?? null;

                if ($column['is_boolean']) {
                    $value = $value ? 1 : 0;
                } elseif ($value === '') {
                    $value = null;
                }

                return [$name => $value];
            })
            ->all();
    }

    private function friendlyError(QueryException $e): string
    {
        // 1451/1452: FK constraint violations — most of this schema's
        // medical-history tables use ON DELETE RESTRICT deliberately, so
        // this is the expected, safe outcome for "delete something that's
        // still referenced," not a bug to hide.
        if (in_array($e->getCode(), ['23000'], true)) {
            return "That row is still referenced by other records (e.g. medical history), so it can't be deleted or changed that way. Remove the dependent records first if you're sure.";
        }

        return 'Database error: '.$e->getMessage();
    }
}
