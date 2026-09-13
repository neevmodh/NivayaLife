/**
 * Drives the report detail page's live state: polls while OCR/summary are
 * still in flight, lets the user correct OCR text, fetches the on-demand
 * detailed explanation, and switches/caches translations. Translations and
 * detailed-explanation history are seeded from the server (ai_responses
 * already on disk) so a page reload never re-triggers a Gemini call for
 * something already generated.
 */
export default function reportProcessing({
    statusUrl,
    ocrTextUrl,
    detailedExplanationUrl,
    retrySummaryUrl,
    translateUrl,
    reuploadUrl,
    csrfToken,
    reportType,
    initialOcrStatus,
    initialOcrText,
    initialAiSummary,
    initialAiSummaryGeneratedAt,
    initialAnalysisMethod,
    initialXrayFindings,
    initialDetectedEntities,
    initialLabResults,
    initialDetailedExplanations,
    initialTranslations,
}) {
    return {
        ocrStatus: initialOcrStatus,
        ocrText: initialOcrText,
        aiSummary: initialAiSummary,
        aiSummaryGeneratedAt: initialAiSummaryGeneratedAt,
        analysisMethod: initialAnalysisMethod,
        xrayFindings: initialXrayFindings,
        detectedEntities: initialDetectedEntities,
        labResults: initialLabResults,
        // ExtractLabResultsJob runs in parallel with the summary job, not
        // after it, so it can still be in flight once the summary is
        // already showing — keep polling a bit longer for blood_test
        // reports specifically, capped so a genuinely failed extraction
        // doesn't poll forever.
        labPollAttempts: 0,
        summaryJobFailed: false,
        pollHandle: null,

        editingOcr: false,
        ocrDraft: '',
        savingOcr: false,
        ocrSaveError: null,

        reuploading: false,
        reuploadError: null,

        detailedExplanations: initialDetailedExplanations || [],
        loadingDetailed: false,
        detailedError: null,

        retryingSummary: false,
        retrySummaryError: null,

        translations: initialTranslations || {},
        currentLanguage: 'en',
        translating: false,
        translatingLang: null,
        translationError: null,

        init() {
            if (this.stillProcessing()) {
                this.pollHandle = setInterval(() => this.poll(), 2500);
            }
        },

        stillProcessing() {
            return ['pending', 'processing'].includes(this.ocrStatus)
                || (this.ocrStatus === 'completed' && !this.aiSummary && !this.summaryJobFailed)
                || (reportType === 'blood_test' && this.ocrStatus === 'completed' && !this.labResults && this.labPollAttempts < 30);
        },

        get phaseLabel() {
            if (['pending', 'processing'].includes(this.ocrStatus)) return 'Reading your report…';
            if (this.ocrStatus === 'completed' && !this.aiSummary && !this.summaryJobFailed) return 'Summarizing…';
            return null;
        },

        async poll() {
            try {
                const res = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
                const json = await res.json();
                this.ocrStatus = json.ocr_status;
                this.ocrText = json.ocr_text;
                this.aiSummary = json.ai_summary;
                this.aiSummaryGeneratedAt = json.ai_summary_generated_at;
                this.analysisMethod = json.analysis_method;
                this.xrayFindings = json.xray_findings;
                this.detectedEntities = json.detected_entities;
                this.labResults = json.lab_results;
                this.summaryJobFailed = json.summary_job_failed;
                this.labPollAttempts += 1;
            } catch (e) { /* try again on the next tick */ }

            if (!this.stillProcessing() && this.pollHandle) {
                clearInterval(this.pollHandle);
                this.pollHandle = null;
            }
        },

        startEditOcr() {
            this.ocrDraft = this.ocrText || '';
            this.ocrSaveError = null;
            this.editingOcr = true;
        },

        async saveOcr() {
            this.savingOcr = true;
            this.ocrSaveError = null;
            try {
                const res = await fetch(ocrTextUrl, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ ocr_text: this.ocrDraft }),
                });
                const json = await res.json();
                if (json.success) {
                    this.ocrText = this.ocrDraft;
                    this.editingOcr = false;
                } else {
                    this.ocrSaveError = json.message || 'Could not save your notes right now — please try again.';
                }
            } catch (e) {
                this.ocrSaveError = 'Network error — please try again.';
            } finally {
                this.savingOcr = false;
            }
        },

        /**
         * Replaces the file behind a failed report and starts over — a full
         * reload afterward (rather than just updating reactive state) is
         * deliberate: the file preview above, and which of image/PDF markup
         * renders it, are computed server-side from the report's mime type
         * at page load, not tracked here, so they need a fresh render too.
         */
        async reuploadFile(file) {
            if (!file) return;

            this.reuploading = true;
            this.reuploadError = null;
            try {
                const body = new FormData();
                body.append('file', file);

                const res = await fetch(reuploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body,
                });
                const json = await res.json();

                if (json.success) {
                    window.location.reload();
                    return;
                }

                this.reuploadError = json.message || Object.values(json.errors || {})[0]?.[0] || 'Could not upload that file — please try again.';
            } catch (e) {
                this.reuploadError = 'Network error — please try again.';
            } finally {
                this.reuploading = false;
            }
        },

        async getDetailedExplanation() {
            this.loadingDetailed = true;
            this.detailedError = null;
            try {
                const res = await fetch(detailedExplanationUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                });
                const json = await res.json();
                if (json.success) {
                    this.detailedExplanations.unshift({ content: json.content, generated_at: json.generated_at });
                } else {
                    this.detailedError = json.message || 'Could not generate an explanation right now.';
                }
            } catch (e) {
                this.detailedError = 'Network error — please try again.';
            } finally {
                this.loadingDetailed = false;
            }
        },

        /** Re-dispatches the same automatic short-summary job — for when the one automatic attempt hit a transient Gemini failure. */
        async retrySummary() {
            this.retryingSummary = true;
            this.retrySummaryError = null;
            try {
                const res = await fetch(retrySummaryUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                });
                const json = await res.json();
                if (json.success) {
                    this.summaryJobFailed = false;
                    if (!this.pollHandle) {
                        this.pollHandle = setInterval(() => this.poll(), 2500);
                    }
                } else {
                    this.retrySummaryError = json.message || 'Could not retry the summary right now.';
                }
            } catch (e) {
                this.retrySummaryError = 'Network error — please try again.';
            } finally {
                this.retryingSummary = false;
            }
        },

        get displayedSummary() {
            if (this.currentLanguage === 'en') return this.aiSummary;
            return this.translations[this.currentLanguage] ?? this.aiSummary;
        },

        async switchLanguage(lang) {
            this.translationError = null;

            if (lang === 'en' || this.translations[lang]) {
                this.currentLanguage = lang;
                return;
            }

            this.translating = true;
            this.translatingLang = lang;
            try {
                const res = await fetch(translateUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ language: lang }),
                });
                const json = await res.json();
                if (json.success) {
                    this.translations[lang] = json.content;
                    this.currentLanguage = lang;
                } else {
                    this.translationError = json.message || 'Could not translate this summary right now.';
                }
            } catch (e) {
                this.translationError = 'Network error — please try again.';
            } finally {
                this.translating = false;
                this.translatingLang = null;
            }
        },

        flagClass(flag) {
            return {
                'bg-nivayalife-pink/20 text-nivayalife-pink-dark': flag === 'low' || flag === 'abnormal',
                'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300': flag === 'high',
                'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-300': flag === 'borderline',
                'bg-nivayalife-mint/60 text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint': flag === 'normal',
                'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-white/60': flag === 'unknown',
            };
        },
    };
}
