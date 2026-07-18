<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveFamilyMember;
use App\Models\FamilyMember;
use App\Services\Pdf\PdfExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TimelineController extends Controller
{
    use ResolvesActiveFamilyMember;

    public function index(Request $request): View
    {
        $user = $request->user();
        $active = $this->resolveActiveFamilyMember($request, $user);

        $type = $request->string('type')->toString();
        $range = $request->string('range')->toString() ?: 'all';
        $search = trim($request->string('q')->toString());

        [$from, $to] = $this->resolveDateRange($range, $request);

        $entries = $this->buildEntries($active, $type, $search, $from, $to);

        $grouped = $entries
            ->groupBy(fn ($e) => $e['date']->format('Y'))
            ->map(fn ($yearEntries) => $yearEntries->groupBy(fn ($e) => $e['date']->format('F')));

        return view('timeline.index', [
            'active' => $active,
            'grouped' => $grouped,
            'entryCount' => $entries->count(),
            'type' => $type,
            'range' => $range,
            'search' => $search,
            'customFrom' => $request->input('from'),
            'customTo' => $request->input('to'),
        ]);
    }

    /** "Doctor-ready" summary of everything in a date range, meant to be handed to a new doctor at a first consultation. */
    public function exportPdf(Request $request, PdfExportService $pdf): Response
    {
        $user = $request->user();
        $active = $this->resolveActiveFamilyMember($request, $user);
        abort_unless($active->hasGrantedAccessTo($user), 403);

        $range = $request->string('range')->toString() ?: '6m';
        [$from, $to] = $this->resolveDateRange($range, $request);

        $reports = $active->reports()
            ->whereBetween('uploaded_at', [$from, $to])
            ->orderBy('uploaded_at')
            ->get();

        $metrics = $active->healthMetrics()
            ->whereBetween('recorded_date', [$from, $to])
            ->orderBy('recorded_date')
            ->get()
            ->groupBy('metric_type');

        $medications = $active->medications()
            ->where(fn ($q) => $q->whereBetween('start_date', [$from, $to])->orWhere('active', true))
            ->orderByDesc('start_date')
            ->get();

        $vaccinations = $active->vaccinations()
            ->whereBetween('date_administered', [$from, $to])
            ->orderBy('date_administered')
            ->get();

        $rangeLabel = $range === 'all' ? 'All time' : $from->format('M j, Y').' – '.$to->format('M j, Y');

        return $pdf->download('timeline.pdf-export', [
            'active' => $active,
            'reports' => $reports,
            'metrics' => $metrics,
            'medications' => $medications,
            'vaccinations' => $vaccinations,
            'rangeLabel' => $rangeLabel,
        ], "health-summary-{$active->unique_health_id}.pdf");
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveDateRange(string $range, Request $request): array
    {
        $to = now()->endOfDay();

        if ($range === 'custom') {
            $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subYears(50)->startOfDay();
            if ($request->filled('to')) {
                $to = Carbon::parse($request->input('to'))->endOfDay();
            }

            return [$from, $to];
        }

        $from = match ($range) {
            '30d' => now()->subDays(30)->startOfDay(),
            '6m' => now()->subMonths(6)->startOfDay(),
            '1y' => now()->subYear()->startOfDay(),
            default => now()->subYears(50)->startOfDay(), // 'all'
        };

        return [$from, $to];
    }

    private function buildEntries(FamilyMember $active, string $type, string $search, Carbon $from, Carbon $to): Collection
    {
        $entries = collect();

        // A search term only matches report text (the only fulltext-indexed
        // source) — other entry types simply aren't text-searchable, so a
        // search narrows the whole feed down to matching reports.
        if ($search !== '') {
            $reports = $active->reports()
                ->whereBetween('uploaded_at', [$from, $to])
                ->whereFullText(['ocr_text', 'ai_summary'], $search)
                ->get();

            foreach ($reports as $report) {
                $entries->push($this->reportEntry($report));
            }

            return $entries->sortByDesc('date')->values();
        }

        $wantsAll = $type === '';
        $wantsReports = $wantsAll || str_starts_with($type, 'report');
        $reportSubtype = str_contains($type, ':') ? explode(':', $type, 2)[1] : null;

        if ($wantsReports) {
            $query = $active->reports()->whereBetween('uploaded_at', [$from, $to]);
            if ($reportSubtype) {
                $query->where('type', $reportSubtype);
            }
            foreach ($query->get() as $report) {
                $entries->push($this->reportEntry($report));
            }
        }

        if ($wantsAll || $type === 'medication') {
            // whereBetween never matches a NULL start_date (common for
            // medications added without one, e.g. via the profile's medicine
            // list) — falling back to "still active" keeps those visible
            // instead of silently vanishing from every date range.
            $medicationsQuery = $active->medications()
                ->where(fn ($q) => $q->whereBetween('start_date', [$from, $to])->orWhere('active', true));

            foreach ($medicationsQuery->get() as $medication) {
                $entries->push([
                    'type' => 'medication',
                    'date' => $medication->start_date ?? $medication->created_at,
                    'title' => $medication->medicine_name,
                    'subtitle' => $medication->dosage,
                    'detail' => trim(($medication->frequency ?? '').($medication->prescribing_doctor ? ' · Prescribed by '.$medication->prescribing_doctor : '')),
                    'model' => $medication,
                ]);
            }
        }

        if ($wantsAll || $type === 'vaccination') {
            foreach ($active->vaccinations()->whereBetween('date_administered', [$from, $to])->get() as $vaccination) {
                $entries->push([
                    'type' => 'vaccination',
                    'date' => $vaccination->date_administered,
                    'title' => $vaccination->vaccine_name,
                    'subtitle' => 'Dose '.$vaccination->dose_number,
                    'detail' => $vaccination->location,
                    'model' => $vaccination,
                ]);
            }
        }

        if ($wantsAll || $type === 'vitals') {
            foreach ($active->healthMetrics()->whereBetween('recorded_date', [$from, $to])->get() as $metric) {
                $entries->push([
                    'type' => 'metric',
                    'date' => $metric->recorded_date,
                    'title' => str_replace('_', ' ', $metric->metric_type),
                    'subtitle' => $metric->value.' '.$metric->unit,
                    'detail' => 'Source: '.str_replace('_', ' ', $metric->source),
                    'model' => $metric,
                    // Only health_metrics rows are comparable per spec — bmi_logs
                    // entries below deliberately omit this key.
                    'compare' => [
                        'id' => $metric->id,
                        'metricType' => $metric->metric_type,
                        'value' => (float) $metric->value,
                        'unit' => $metric->unit,
                        'date' => $metric->recorded_date->toDateString(),
                        'dateLabel' => $metric->recorded_date->format('M j, Y'),
                    ],
                ]);
            }

            foreach ($active->bmiLogs()->whereBetween('recorded_date', [$from, $to])->get() as $bmi) {
                $entries->push([
                    'type' => 'metric',
                    'date' => $bmi->recorded_date,
                    'title' => 'BMI',
                    'subtitle' => $bmi->bmi_value.' ('.ucfirst($bmi->bmi_category).')',
                    'detail' => $bmi->height_cm.'cm, '.$bmi->weight_kg.'kg',
                    'model' => $bmi,
                ]);
            }
        }

        return $entries->sortByDesc('date')->values();
    }

    private function reportEntry($report): array
    {
        return [
            'type' => 'report',
            'date' => $report->report_date ?? $report->uploaded_at ?? $report->created_at,
            'title' => $report->typeLabel(),
            'subtitle' => $report->hospital_or_clinic_name,
            'detail' => $report->ai_summary,
            'model' => $report,
        ];
    }
}
