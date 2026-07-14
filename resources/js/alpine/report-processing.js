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
    csrfToken,
    initialOcrStatus,
    initialOcrText,
    initialAiSummary,
    initialAiSummaryGeneratedAt,
    initialDetailedExplanations,
    initialTranslations,
}) {
    return {
        ocrStatus: initialOcrStatus,
        ocrText: initialOcrText,
        aiSummary: initialAiSummary,
        aiSummaryGeneratedAt: initialAiSummaryGeneratedAt,
        summaryJobFailed: false,
        pollHandle: null,

        editingOcr: false,
        ocrDraft: '',
        savingOcr: false,

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
                || (this.ocrStatus === 'completed' && !this.aiSummary && !this.summaryJobFailed);
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
                this.summaryJobFailed = json.summary_job_failed;
            } catch (e) { /* try again on the next tick */ }

            if (!this.stillProcessing() && this.pollHandle) {
                clearInterval(this.pollHandle);
                this.pollHandle = null;
            }
        },

        startEditOcr() {
            this.ocrDraft = this.ocrText || '';
            this.editingOcr = true;
        },

        async saveOcr() {
            this.savingOcr = true;
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
                }
            } finally {
                this.savingOcr = false;
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
    };
}
