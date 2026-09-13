@php
    $metricLabels = [
        'blood_pressure_systolic' => 'BP (Systolic)', 'blood_pressure_diastolic' => 'BP (Diastolic)',
        'blood_sugar_fasting' => 'Blood Sugar (Fasting)', 'blood_sugar_pp' => 'Blood Sugar (PP)',
        'hba1c' => 'HbA1c', 'cholesterol_total' => 'Cholesterol (Total)',
        'cholesterol_ldl' => 'Cholesterol (LDL)', 'cholesterol_hdl' => 'Cholesterol (HDL)',
        'hemoglobin' => 'Hemoglobin', 'other' => 'Other',
    ];
    $isImage = str_starts_with($report->mime_type ?? '', 'image/');
    $isPdf = ($report->mime_type ?? '') === 'application/pdf';

    $ocrBadge = [
        'pending' => ['label' => 'Queued', 'class' => 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-white/60'],
        'processing' => ['label' => 'Reading…', 'class' => 'bg-nivayalife-yellow/30 text-amber-700 dark:text-nivayalife-yellow'],
        'completed' => ['label' => 'Ready', 'class' => 'bg-nivayalife-mint text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint'],
        'failed' => ['label' => 'Failed', 'class' => 'bg-nivayalife-pink/30 text-nivayalife-pink-dark'],
    ];
    $badge = $ocrBadge[$report->ocr_status] ?? $ocrBadge['pending'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3.5">
            <span class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white text-nivayalife-green shadow-nivayalife-sm dark:bg-white/10 dark:text-nivayalife-mint" aria-hidden="true">
                <x-report-type-icon :type="$report->type" class="h-6 w-6" />
            </span>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold leading-tight text-nivayalife-ink dark:text-white">{{ $report->typeLabel() }}</h2>
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                </div>
                <p class="mt-0.5 truncate text-sm text-nivayalife-muted">
                    {{ $report->familyMember->full_name }}
                    @if($report->report_date) &middot; {{ $report->report_date->format('M j, Y') }} @endif
                </p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8"
        x-data="reportProcessing({
            statusUrl: @js(route('reports.status', $report)),
            ocrTextUrl: @js(route('reports.ocr-text', $report)),
            detailedExplanationUrl: @js(route('reports.detailed-explanation', $report)),
            retrySummaryUrl: @js(route('reports.retry-summary', $report)),
            translateUrl: @js(route('reports.translate', $report)),
            reuploadUrl: @js(route('reports.reupload', $report)),
            csrfToken: @js(csrf_token()),
            reportType: @js($report->type),
            initialOcrStatus: @js($report->ocr_status),
            initialOcrText: @js($report->ocr_text),
            initialAiSummary: @js($report->ai_summary),
            initialAiSummaryGeneratedAt: @js($report->ai_summary_generated_at?->toIso8601String()),
            initialAnalysisMethod: @js($report->analysis_method),
            initialXrayFindings: @js($report->xray_findings),
            initialDetectedEntities: @js($report->detected_entities),
            initialLabResults: @js($report->lab_results),
            initialDetailedExplanations: @js($detailedExplanations),
            initialTranslations: @js($translations),
        })"
    >
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-nivayalife-green hover:underline">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Back to reports
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.file', $report) }}" target="_blank"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-nivayalife-ink transition hover:-translate-y-0.5 hover:bg-nivayalife-cream active:translate-y-0 dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4v12m0-12 4 4m-4-4-4 4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" transform="rotate(180 12 12)"/></svg>
                    Original
                </a>
                <a href="{{ route('shares.create') }}?report={{ $report->id }}"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-nivayalife-green px-3 py-1.5 text-xs font-semibold text-white shadow-nivayalife-sm transition hover:-translate-y-0.5 hover:bg-nivayalife-green-dark active:translate-y-0 active:scale-95">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M18 8a3 3 0 1 0-2.83-4H15a3 3 0 0 0 .09 4.26L8.9 11.7a3 3 0 1 0 0 4.6l6.19 3.44A3 3 0 1 0 15 17.7l-6.19-3.44a3 3 0 0 0 0-.52L15 10.3c.52.44 1.19.7 1.91.7A3 3 0 0 0 18 8Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
                    Share
                </a>
            </div>
        </div>

        {{-- Live processing status --}}
        <div x-show="phaseLabel" x-cloak class="mb-4 flex items-center gap-2.5 rounded-nivayalife bg-nivayalife-mint/60 px-4 py-3 text-sm font-medium text-nivayalife-green dark:bg-nivayalife-green/15 dark:text-nivayalife-mint">
            <svg class="h-4 w-4 flex-shrink-0 animate-spin" viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 1 1-9-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
            <span x-text="phaseLabel"></span>
        </div>

        {{-- File card --}}
        <div class="overflow-hidden rounded-nivayalife bg-white shadow-nivayalife-sm dark:bg-white/5">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 p-4 dark:border-white/10">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-nivayalife-ink dark:text-white">{{ $report->original_filename }}</p>
                    <p class="text-xs text-nivayalife-muted">
                        {{ $report->report_date?->format('M j, Y') ?? '—' }}
                        @if($report->hospital_or_clinic_name) &middot; {{ $report->hospital_or_clinic_name }} @endif
                        @if($report->doctor_name) &middot; Dr. {{ $report->doctor_name }} @endif
                    </p>
                </div>
                <a href="{{ route('reports.file', $report) }}" target="_blank" class="flex-shrink-0 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-nivayalife-ink hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">Open original</a>
            </div>

            @if($isImage)
                <img src="{{ route('reports.file', $report) }}" alt="{{ $report->original_filename }}" class="max-h-[420px] w-full object-contain bg-nivayalife-cream dark:bg-white/5">
            @elseif($isPdf)
                <iframe src="{{ route('reports.file', $report) }}" class="h-[420px] w-full" title="{{ $report->original_filename }}"></iframe>
            @endif
        </div>

        {{-- Short AI summary --}}
        <div class="mt-6 rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
            <div class="flex items-center justify-between gap-3">
                <h3 class="flex items-center gap-2 text-sm font-bold text-nivayalife-ink dark:text-white">
                    <svg class="h-4 w-4 text-nivayalife-green" viewBox="0 0 24 24" fill="none"><path d="M12 2 3.5 6v6c0 5 3.6 8.7 8.5 10 4.9-1.3 8.5-5 8.5-10V6L12 2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Summary
                </h3>

                <div x-show="aiSummary" x-cloak class="flex gap-1 rounded-full bg-nivayalife-cream p-0.5 text-xs font-semibold dark:bg-white/10">
                    <button type="button" @click="switchLanguage('en')" :class="currentLanguage === 'en' ? 'bg-nivayalife-green text-white' : 'text-nivayalife-muted'" class="rounded-full px-2.5 py-1 transition">EN</button>
                    <button type="button" @click="switchLanguage('hi')" :class="currentLanguage === 'hi' ? 'bg-nivayalife-green text-white' : 'text-nivayalife-muted'" class="rounded-full px-2.5 py-1 transition">
                        <span x-show="translatingLang === 'hi'">&hellip;</span><span x-show="translatingLang !== 'hi'">HI</span>
                    </button>
                    <button type="button" @click="switchLanguage('gu')" :class="currentLanguage === 'gu' ? 'bg-nivayalife-green text-white' : 'text-nivayalife-muted'" class="rounded-full px-2.5 py-1 transition">
                        <span x-show="translatingLang === 'gu'">&hellip;</span><span x-show="translatingLang !== 'gu'">GU</span>
                    </button>
                </div>
            </div>

            <p x-show="translationError" x-cloak x-text="translationError" class="mt-2 text-xs font-semibold text-nivayalife-pink-dark"></p>

            <p x-show="aiSummary && analysisMethod === 'vision'" x-cloak class="mt-3 inline-block rounded-full bg-nivayalife-mint/60 px-2.5 py-0.5 text-xs font-semibold text-nivayalife-green dark:bg-nivayalife-green/15 dark:text-nivayalife-mint">AI visual analysis — no readable text found, described from the image</p>
            <p x-show="aiSummary" x-cloak class="mt-3 whitespace-pre-line text-sm text-nivayalife-ink dark:text-white" x-text="displayedSummary"></p>
            <div x-show="aiSummary && currentLanguage === 'en'" x-cloak class="mt-2 flex items-center justify-between gap-2">
                <p class="text-xs text-nivayalife-muted" x-text="aiSummaryGeneratedAt ? 'Generated ' + new Date(aiSummaryGeneratedAt).toLocaleString() : ''"></p>
                <button type="button" @click="startEditOcr()" x-show="!editingOcr" class="flex-shrink-0 text-xs font-semibold text-nivayalife-muted hover:text-nivayalife-green hover:underline">Something misread?</button>
            </div>

            <div x-show="xrayFindings && xrayFindings.length" x-cloak class="mt-3 rounded-xl bg-nivayalife-cream/60 p-3 dark:bg-white/5">
                <p class="text-xs font-semibold text-nivayalife-ink dark:text-white">Model findings (chest X-ray classifier)</p>
                <ul class="mt-1.5 space-y-1">
                    <template x-for="finding in (xrayFindings || []).slice(0, 6)" :key="finding.pathology">
                        <li class="flex items-center justify-between text-xs text-nivayalife-muted">
                            <span x-text="finding.pathology"></span>
                            <span x-text="Math.round(finding.probability * 100) + '%'"></span>
                        </li>
                    </template>
                </ul>
            </div>

            <div x-show="(detectedEntities || []).filter(e => !e.negated).length" x-cloak class="mt-3 rounded-xl bg-nivayalife-cream/60 p-3 dark:bg-white/5">
                <p class="text-xs font-semibold text-nivayalife-ink dark:text-white">Mentioned in this report</p>
                <div class="mt-1.5 flex flex-wrap gap-1.5">
                    <template x-for="entity in (detectedEntities || []).filter(e => !e.negated)" :key="entity.text">
                        <span class="rounded-full bg-white px-2.5 py-0.5 text-xs text-nivayalife-ink dark:bg-white/10 dark:text-white" x-text="entity.text"></span>
                    </template>
                </div>
            </div>

            <template x-if="!aiSummary && ocrStatus === 'failed'">
                <div class="mt-3 rounded-xl bg-nivayalife-cream/60 p-4 text-center dark:bg-white/5">
                    <p class="text-sm text-nivayalife-ink dark:text-white">We couldn't read this document automatically.</p>
                    <p class="mt-1 text-xs text-nivayalife-muted">A blurry or angled photo is the usual cause — reuploading a clearer shot often fixes it. You can still view the original file above, or add your own notes below.</p>
                    <p x-show="reuploadError" x-cloak x-text="reuploadError" class="mt-2 text-xs font-semibold text-nivayalife-pink-dark"></p>
                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                        <input type="file" x-ref="reuploadInput" accept=".pdf,.jpg,.jpeg,.png,.webp,.tiff,.tif,.bmp,.gif,.docx,application/pdf,image/jpeg,image/png,image/webp,image/tiff,image/bmp,image/gif,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="hidden" @change="reuploadFile($event.target.files[0]); $event.target.value = ''">
                        <button type="button" @click="$refs.reuploadInput.click()" :disabled="reuploading" class="rounded-lg bg-nivayalife-green px-4 py-2 text-xs font-semibold text-white hover:bg-nivayalife-green-dark disabled:opacity-50">
                            <span x-show="!reuploading">Reupload document</span>
                            <span x-show="reuploading">Uploading&hellip;</span>
                        </button>
                        <button type="button" @click="startEditOcr()" class="rounded-lg border border-gray-200 px-4 py-2 text-xs font-semibold text-nivayalife-ink hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">Add notes instead</button>
                    </div>
                </div>
            </template>

            <template x-if="!aiSummary && summaryJobFailed && ocrStatus === 'completed'">
                <div class="mt-3">
                    <p class="text-sm text-nivayalife-muted">Summary couldn't be generated for this report — this usually means the AI service was briefly unavailable. You can still request a detailed explanation below, or try the summary again.</p>
                    <p x-show="retrySummaryError" x-cloak x-text="retrySummaryError" class="mt-2 text-xs font-semibold text-nivayalife-pink-dark"></p>
                    <button type="button" @click="retrySummary()" :disabled="retryingSummary" class="mt-2 rounded-lg border border-gray-200 px-4 py-2 text-xs font-semibold text-nivayalife-ink hover:bg-nivayalife-cream disabled:opacity-50 dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                        <span x-show="!retryingSummary">Try summary again</span>
                        <span x-show="retryingSummary">Retrying…</span>
                    </button>
                </div>
            </template>
        </div>

        {{-- Structured extraction, rendered per report type --}}
        @if(($report->structured_data['not_a_medical_report'] ?? false))
            {{-- Extraction decided this isn't a medical document (e.g. a saved
                 email or receipt). Say so plainly instead of letting the AI
                 summary imply it read a real report. --}}
            <div class="mt-6 rounded-nivayalife bg-nivayalife-yellow/15 p-5 dark:bg-white/5">
                <p class="text-sm font-bold text-nivayalife-ink dark:text-white">This doesn't look like a medical report</p>
                <p class="mt-1 text-sm text-nivayalife-muted">We couldn't find medical information in this file. It's still saved and you can open the original above — but you may want to re-upload the right document, or change its type.</p>
            </div>
        @endif

        @php($structured = \App\Services\Reports\StructuredPresenter::for($report))
        @if($structured)
            @include($structured['partial'], $structured['props'])
        @endif

        {{-- Structured lab results table --}}
        <div x-show="labResults && labResults.length" x-cloak class="mt-6 overflow-hidden rounded-nivayalife bg-white shadow-nivayalife-sm dark:bg-white/5">
            <h3 class="p-5 pb-0 text-sm font-bold text-nivayalife-ink dark:text-white">Lab Results</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-[11px] font-semibold uppercase tracking-wide text-nivayalife-muted dark:border-white/10">
                            <th class="px-5 py-2">Test</th>
                            <th class="px-3 py-2">Result</th>
                            <th class="px-3 py-2">Reference Range</th>
                            <th class="px-5 py-2 text-right">Flag</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in (labResults || [])" :key="row.test + row.value">
                            <tr class="border-b border-gray-50 last:border-0 dark:border-white/5">
                                <td class="px-5 py-2.5 font-medium text-nivayalife-ink dark:text-white" x-text="row.test"></td>
                                <td class="px-3 py-2.5 text-nivayalife-ink dark:text-white" x-text="row.value + (row.unit ? ' ' + row.unit : '')"></td>
                                <td class="px-3 py-2.5 text-nivayalife-muted" x-text="row.reference_range || '—'"></td>
                                <td class="px-5 py-2.5 text-right">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="{
                                            'bg-nivayalife-pink/20 text-nivayalife-pink-dark': row.flag === 'low' || row.flag === 'abnormal',
                                            'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300': row.flag === 'high',
                                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-300': row.flag === 'borderline',
                                            'bg-nivayalife-mint/60 text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint': row.flag === 'normal',
                                            'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-white/60': row.flag === 'unknown',
                                        }"
                                        x-text="row.flag.charAt(0).toUpperCase() + row.flag.slice(1)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p class="p-5 pt-3 text-xs text-nivayalife-muted">Extracted automatically — always confirm against the original document above before making any decisions.</p>
        </div>

        {{-- Trend charts --}}
        @if($metricHistories->isNotEmpty())
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach($metricHistories as $history)
                    <div class="rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                        <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">{{ $metricLabels[$history['metric_type']] ?? Str::headline($history['metric_type']) }} over time</h3>
                        <div class="mt-2" x-data="adminChart({
                            type: 'line',
                            series: [{ name: @js($metricLabels[$history['metric_type']] ?? Str::headline($history['metric_type'])), data: @js(collect($history['points'])->map(fn ($p) => ['x' => $p['date'], 'y' => $p['value']])) }],
                            options: {
                                height: 200,
                                colors: ['#14503F'],
                                stroke: { curve: 'smooth', width: 2 },
                                dataLabels: { enabled: false },
                                xaxis: { type: 'datetime', labels: { format: 'MMM d' } },
                                yaxis: { title: { text: @js($history['unit'] ?? '') } },
                                grid: { borderColor: 'rgba(148,163,184,0.2)' },
                            },
                        })"></div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Detailed explanation --}}
        <div class="mt-6 rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">Detailed explanation</h3>
                <button type="button" @click="getDetailedExplanation()" :disabled="loadingDetailed || ocrStatus !== 'completed'"
                    class="rounded-lg bg-nivayalife-green px-4 py-2 text-xs font-semibold text-white transition hover:bg-nivayalife-green-dark disabled:cursor-not-allowed disabled:opacity-40">
                    <span x-show="!loadingDetailed">Get detailed explanation</span>
                    <span x-show="loadingDetailed">Generating&hellip;</span>
                </button>
            </div>

            <p x-show="detailedError" x-cloak x-text="detailedError" class="mt-2 text-xs font-semibold text-nivayalife-pink-dark"></p>

            <template x-if="detailedExplanations.length === 0 && !loadingDetailed">
                <p class="mt-3 text-sm text-nivayalife-muted">Get a fuller, plain-language explanation of what this report means and questions you might want to ask your doctor.</p>
            </template>

            <div class="mt-3 space-y-4">
                <template x-for="(explanation, index) in detailedExplanations" :key="explanation.generated_at + index">
                    <div class="rounded-xl bg-nivayalife-cream/60 p-4 dark:bg-white/5">
                        <p class="whitespace-pre-line text-sm text-nivayalife-ink dark:text-white" x-text="explanation.content"></p>
                        <p class="mt-2 text-xs text-nivayalife-muted" x-text="'Generated ' + new Date(explanation.generated_at).toLocaleString()"></p>
                    </div>
                </template>
            </div>
        </div>

        {{-- Correct a misread — no raw OCR dump shown; this only opens on request --}}
        <div x-show="editingOcr" x-cloak class="mt-6 rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
            <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">Correct the report text</h3>
            <p class="mt-1 text-xs text-nivayalife-muted">Fix anything the reader got wrong — the summary above will use your correction next time it's regenerated.</p>
            <textarea x-model="ocrDraft" rows="10" class="mt-3 w-full rounded-xl border border-gray-200 bg-nivayalife-cream/40 p-3 text-sm text-nivayalife-ink focus:border-nivayalife-green focus:outline-none focus:ring-2 focus:ring-nivayalife-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white"></textarea>
            <p x-show="ocrSaveError" x-cloak x-text="ocrSaveError" class="mt-2 text-xs font-semibold text-nivayalife-pink-dark"></p>
            <div class="mt-3 flex justify-end gap-2">
                <button type="button" @click="editingOcr = false" class="rounded-lg px-4 py-2 text-xs font-semibold text-nivayalife-muted hover:text-nivayalife-ink">Cancel</button>
                <button type="button" @click="saveOcr()" :disabled="savingOcr" class="rounded-lg bg-nivayalife-green px-4 py-2 text-xs font-semibold text-white hover:bg-nivayalife-green-dark disabled:opacity-50">
                    <span x-show="!savingOcr">Save</span>
                    <span x-show="savingOcr">Saving&hellip;</span>
                </button>
            </div>
        </div>

        {{-- Extracted health metrics --}}
        @if($report->healthMetrics->isNotEmpty())
            <div class="mt-6 rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">Values found in this report</h3>
                <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach($report->healthMetrics as $metric)
                        <div class="rounded-xl bg-nivayalife-cream/60 p-3 dark:bg-white/5">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-nivayalife-muted">{{ $metricLabels[$metric->metric_type] ?? Str::headline($metric->metric_type) }}</dt>
                            <dd class="mt-0.5 text-sm font-bold text-nivayalife-ink dark:text-white">{{ rtrim(rtrim(number_format($metric->value, 2), '0'), '.') }} {{ $metric->unit }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif
    </div>
</x-app-layout>
