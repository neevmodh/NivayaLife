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
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">{{ $report->typeLabel() }}</h2>
        <p class="mt-1 text-sm text-novix-muted">{{ $report->familyMember->full_name }}</p>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8"
        x-data="reportProcessing({
            statusUrl: @js(route('reports.status', $report)),
            ocrTextUrl: @js(route('reports.ocr-text', $report)),
            detailedExplanationUrl: @js(route('reports.detailed-explanation', $report)),
            retrySummaryUrl: @js(route('reports.retry-summary', $report)),
            translateUrl: @js(route('reports.translate', $report)),
            csrfToken: @js(csrf_token()),
            initialOcrStatus: @js($report->ocr_status),
            initialOcrText: @js($report->ocr_text),
            initialAiSummary: @js($report->ai_summary),
            initialAiSummaryGeneratedAt: @js($report->ai_summary_generated_at?->toIso8601String()),
            initialDetailedExplanations: @js($detailedExplanations),
            initialTranslations: @js($translations),
        })"
    >
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-novix-green hover:underline">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Back to reports
            </a>
            <a href="{{ route('shares.create') }}?report={{ $report->id }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M18 8a3 3 0 1 0-2.83-4H15a3 3 0 0 0 .09 4.26L8.9 11.7a3 3 0 1 0 0 4.6l6.19 3.44A3 3 0 1 0 15 17.7l-6.19-3.44a3 3 0 0 0 0-.52L15 10.3c.52.44 1.19.7 1.91.7A3 3 0 0 0 18 8Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
                Share
            </a>
        </div>

        {{-- Live processing status --}}
        <div x-show="phaseLabel" x-cloak class="mb-4 flex items-center gap-2.5 rounded-novix bg-novix-mint/60 px-4 py-3 text-sm font-medium text-novix-green dark:bg-novix-green/15 dark:text-novix-mint">
            <svg class="h-4 w-4 flex-shrink-0 animate-spin" viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 1 1-9-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
            <span x-text="phaseLabel"></span>
        </div>

        {{-- File card --}}
        <div class="overflow-hidden rounded-novix bg-white shadow-novix-sm dark:bg-white/5">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 p-4 dark:border-white/10">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-novix-ink dark:text-white">{{ $report->original_filename }}</p>
                    <p class="text-xs text-novix-muted">
                        {{ $report->report_date?->format('M j, Y') ?? '—' }}
                        @if($report->hospital_or_clinic_name) &middot; {{ $report->hospital_or_clinic_name }} @endif
                        @if($report->doctor_name) &middot; Dr. {{ $report->doctor_name }} @endif
                    </p>
                </div>
                <a href="{{ route('reports.file', $report) }}" target="_blank" class="flex-shrink-0 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">Open original</a>
            </div>

            @if($isImage)
                <img src="{{ route('reports.file', $report) }}" alt="{{ $report->original_filename }}" class="max-h-[420px] w-full object-contain bg-novix-cream dark:bg-white/5">
            @elseif($isPdf)
                <iframe src="{{ route('reports.file', $report) }}" class="h-[420px] w-full" title="{{ $report->original_filename }}"></iframe>
            @endif
        </div>

        {{-- Short AI summary --}}
        <div class="mt-6 rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
            <div class="flex items-center justify-between gap-3">
                <h3 class="flex items-center gap-2 text-sm font-bold text-novix-ink dark:text-white">
                    <svg class="h-4 w-4 text-novix-green" viewBox="0 0 24 24" fill="none"><path d="M12 2 3.5 6v6c0 5 3.6 8.7 8.5 10 4.9-1.3 8.5-5 8.5-10V6L12 2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Summary
                </h3>

                <div x-show="aiSummary" x-cloak class="flex gap-1 rounded-full bg-novix-cream p-0.5 text-xs font-semibold dark:bg-white/10">
                    <button type="button" @click="switchLanguage('en')" :class="currentLanguage === 'en' ? 'bg-novix-green text-white' : 'text-novix-muted'" class="rounded-full px-2.5 py-1 transition">EN</button>
                    <button type="button" @click="switchLanguage('hi')" :class="currentLanguage === 'hi' ? 'bg-novix-green text-white' : 'text-novix-muted'" class="rounded-full px-2.5 py-1 transition">
                        <span x-show="translatingLang === 'hi'">&hellip;</span><span x-show="translatingLang !== 'hi'">HI</span>
                    </button>
                    <button type="button" @click="switchLanguage('gu')" :class="currentLanguage === 'gu' ? 'bg-novix-green text-white' : 'text-novix-muted'" class="rounded-full px-2.5 py-1 transition">
                        <span x-show="translatingLang === 'gu'">&hellip;</span><span x-show="translatingLang !== 'gu'">GU</span>
                    </button>
                </div>
            </div>

            <p x-show="translationError" x-cloak x-text="translationError" class="mt-2 text-xs font-semibold text-novix-pink-dark"></p>

            <p x-show="aiSummary" x-cloak class="mt-3 whitespace-pre-line text-sm text-novix-ink dark:text-white" x-text="displayedSummary"></p>
            <p x-show="aiSummary && currentLanguage === 'en'" x-cloak class="mt-2 text-xs text-novix-muted" x-text="aiSummaryGeneratedAt ? 'Generated ' + new Date(aiSummaryGeneratedAt).toLocaleString() : ''"></p>

            <template x-if="!aiSummary && ocrStatus === 'failed'">
                <div class="mt-3 rounded-xl bg-novix-cream/60 p-4 text-center dark:bg-white/5">
                    <p class="text-sm text-novix-ink dark:text-white">We couldn't read this document automatically.</p>
                    <p class="mt-1 text-xs text-novix-muted">You can still view the original file above, or add your own notes below.</p>
                    <button type="button" @click="startEditOcr()" class="mt-3 rounded-lg bg-novix-green px-4 py-2 text-xs font-semibold text-white hover:bg-novix-green-dark">Add notes</button>
                </div>
            </template>

            <template x-if="!aiSummary && summaryJobFailed && ocrStatus === 'completed'">
                <div class="mt-3">
                    <p class="text-sm text-novix-muted">Summary couldn't be generated for this report — this usually means the AI service was briefly unavailable. You can still request a detailed explanation below, or try the summary again.</p>
                    <p x-show="retrySummaryError" x-cloak x-text="retrySummaryError" class="mt-2 text-xs font-semibold text-novix-pink-dark"></p>
                    <button type="button" @click="retrySummary()" :disabled="retryingSummary" class="mt-2 rounded-lg border border-gray-200 px-4 py-2 text-xs font-semibold text-novix-ink hover:bg-novix-cream disabled:opacity-50 dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                        <span x-show="!retryingSummary">Try summary again</span>
                        <span x-show="retryingSummary">Retrying…</span>
                    </button>
                </div>
            </template>
        </div>

        {{-- Detailed explanation --}}
        <div class="mt-6 rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Detailed explanation</h3>
                <button type="button" @click="getDetailedExplanation()" :disabled="loadingDetailed || ocrStatus !== 'completed'"
                    class="rounded-lg bg-novix-green px-4 py-2 text-xs font-semibold text-white transition hover:bg-novix-green-dark disabled:cursor-not-allowed disabled:opacity-40">
                    <span x-show="!loadingDetailed">Get detailed explanation</span>
                    <span x-show="loadingDetailed">Generating&hellip;</span>
                </button>
            </div>

            <p x-show="detailedError" x-cloak x-text="detailedError" class="mt-2 text-xs font-semibold text-novix-pink-dark"></p>

            <template x-if="detailedExplanations.length === 0 && !loadingDetailed">
                <p class="mt-3 text-sm text-novix-muted">Get a fuller, plain-language explanation of what this report means and questions you might want to ask your doctor.</p>
            </template>

            <div class="mt-3 space-y-4">
                <template x-for="(explanation, index) in detailedExplanations" :key="explanation.generated_at + index">
                    <div class="rounded-xl bg-novix-cream/60 p-4 dark:bg-white/5">
                        <p class="whitespace-pre-line text-sm text-novix-ink dark:text-white" x-text="explanation.content"></p>
                        <p class="mt-2 text-xs text-novix-muted" x-text="'Generated ' + new Date(explanation.generated_at).toLocaleString()"></p>
                    </div>
                </template>
            </div>
        </div>

        {{-- Extracted text --}}
        <div class="mt-6 rounded-novix bg-white shadow-novix-sm dark:bg-white/5" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="flex w-full items-center justify-between p-5 text-left">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Extracted Text</h3>
                <svg class="h-4 w-4 text-novix-muted transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <div x-show="open" x-cloak x-transition class="border-t border-gray-100 p-5 dark:border-white/10">
                <template x-if="!editingOcr">
                    <div>
                        <p class="whitespace-pre-line text-sm text-novix-ink dark:text-white" x-text="ocrText || 'No text extracted yet.'"></p>
                        <button type="button" @click="startEditOcr()" class="mt-3 text-xs font-semibold text-novix-green hover:underline">Edit / correct text</button>
                    </div>
                </template>
                <template x-if="editingOcr">
                    <div>
                        <textarea x-model="ocrDraft" rows="10" class="w-full rounded-xl border border-gray-200 bg-novix-cream/40 p-3 text-sm text-novix-ink focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white"></textarea>
                        <div class="mt-3 flex justify-end gap-2">
                            <button type="button" @click="editingOcr = false" class="rounded-lg px-4 py-2 text-xs font-semibold text-novix-muted hover:text-novix-ink">Cancel</button>
                            <button type="button" @click="saveOcr()" :disabled="savingOcr" class="rounded-lg bg-novix-green px-4 py-2 text-xs font-semibold text-white hover:bg-novix-green-dark disabled:opacity-50">
                                <span x-show="!savingOcr">Save</span>
                                <span x-show="savingOcr">Saving&hellip;</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Extracted health metrics --}}
        @if($report->healthMetrics->isNotEmpty())
            <div class="mt-6 rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Values found in this report</h3>
                <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach($report->healthMetrics as $metric)
                        <div class="rounded-xl bg-novix-cream/60 p-3 dark:bg-white/5">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-novix-muted">{{ $metricLabels[$metric->metric_type] ?? Str::headline($metric->metric_type) }}</dt>
                            <dd class="mt-0.5 text-sm font-bold text-novix-ink dark:text-white">{{ rtrim(rtrim(number_format($metric->value, 2), '0'), '.') }} {{ $metric->unit }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif
    </div>
</x-app-layout>
