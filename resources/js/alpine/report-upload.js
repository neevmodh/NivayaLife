/**
 * Batch report upload: each selected file gets its own metadata form, own
 * thumbnail, and own XHR (for real upload-progress events, which fetch()
 * doesn't expose) — so one slow/failed file never blocks the others.
 */
// Filenames commonly use "_"/"-" as word separators, which \b treats as
// word characters (no boundary) — so short tokens use an explicit
// non-letter lookaround instead of \b to still match "lab_report.pdf".
const NOT_LETTER = '(?:^|[^a-z])';
const NOT_LETTER_END = '(?:$|[^a-z])';
const TYPE_RULES = [
    [/rx|prescription|script/i, 'prescription'],
    [/x-?ray/i, 'xray'],
    [/sono(graphy)?|ultrasound|usg/i, 'sonography'],
    [new RegExp(`${NOT_LETTER}mri${NOT_LETTER_END}|${NOT_LETTER}ct${NOT_LETTER_END}|scan`, 'i'), 'mri_ct'],
    [/ecg|ekg|cardio/i, 'ecg'],
    [/insurance|policy|mediclaim/i, 'insurance'],
    [/bill|invoice|receipt|payment/i, 'bill'],
    [/dental|tooth|teeth|orthodont/i, 'dental'],
    [/discharge/i, 'discharge_summary'],
    [/biopsy|histopath|cytology|fnac/i, 'pathology'],
    [/ophthalm|optometr|eye[\s_-]?(care|test|exam)/i, 'eye_care'],
    [new RegExp(`blood|cbc|${NOT_LETTER}lab${NOT_LETTER_END}|lft|kft|lipid|sugar|glucose|hba1c`, 'i'), 'blood_test'],
];

function guessType(filename) {
    const match = TYPE_RULES.find(([pattern]) => pattern.test(filename));
    return match ? match[1] : 'other';
}

function compressImage(file) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                // 2200px keeps small lab-report table text legible to
                // Tesseract after the server-side cleanup pass, while still
                // cutting a typical 12MP phone photo down to a fraction of
                // its upload size.
                const maxDim = 2200;
                let { width, height } = img;
                if (width > maxDim || height > maxDim) {
                    const scale = maxDim / Math.max(width, height);
                    width = Math.round(width * scale);
                    height = Math.round(height * scale);
                }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                canvas.toBlob((blob) => resolve(blob || file), 'image/jpeg', 0.87);
            };
            img.onerror = () => resolve(file);
            img.src = e.target.result;
        };
        reader.onerror = () => resolve(file);
        reader.readAsDataURL(file);
    });
}

export default function reportUpload({ familyMemberId, uploadUrl, detectUrl, csrfToken, reportsIndexUrl }) {
    return {
        files: [],
        dragging: false,
        submitting: false,
        typeOptions: [
            { value: 'blood_test', label: 'Blood Test', icon: '\u{1FA78}' },
            { value: 'prescription', label: 'Prescription', icon: '\u{1F48A}' },
            { value: 'xray', label: 'X-Ray', icon: '\u{1F9B4}' },
            { value: 'sonography', label: 'Sonography', icon: '\u{1F50A}' },
            { value: 'mri_ct', label: 'MRI/CT', icon: '\u{1F9E0}' },
            { value: 'insurance', label: 'Insurance', icon: '\u{1F4C4}' },
            { value: 'bill', label: 'Bill', icon: '\u{1F9FE}' },
            { value: 'ecg', label: 'ECG', icon: '\u{1F493}' },
            { value: 'dental', label: 'Dental', icon: '\u{1F9B7}' },
            { value: 'discharge_summary', label: 'Discharge Summary', icon: '\u{1F3E5}' },
            { value: 'pathology', label: 'Pathology', icon: '\u{1F9EA}' },
            { value: 'eye_care', label: 'Eye Care', icon: '\u{1F441}\u{FE0F}' },
            { value: 'other', label: 'Other', icon: '\u{1F4CB}' },
        ],

        onFilesSelected(fileList) {
            const today = new Date().toISOString().slice(0, 10);

            for (const file of Array.from(fileList)) {
                const isDocx = file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || /\.docx$/i.test(file.name);
                const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
                const isImage = /^image\//.test(file.type) || /\.(jpe?g|png|webp|tiff?|bmp|gif)$/i.test(file.name);
                const isDocument = isPdf || isDocx;
                if (!isDocument && !isImage) continue;
                if (file.size > 10 * 1024 * 1024) continue;

                const entry = {
                    id: crypto.randomUUID(),
                    file,
                    isDocument,
                    name: file.name,
                    previewUrl: isDocument ? null : URL.createObjectURL(file),
                    type: guessType(file.name),
                    reportDate: today,
                    hospital: '',
                    doctor: '',
                    touched: { type: false, reportDate: false, hospital: false, doctor: false },
                    detecting: true,
                    ocrPreview: '',
                    ocrMethod: null,
                    showOcrPreview: false,
                    blobPromise: null,
                    progress: 0,
                    status: 'draft', // draft | uploading | done | error | duplicate
                    error: null,
                    redirectUrl: null,
                    existingReportUrl: null,
                };

                this.files.push(entry);

                // Detection must mutate the reactive element Alpine tracks
                // in the `files` array, not the plain object we just built
                // — pushing doesn't retroactively make `entry` itself
                // reactive, so writes through this local reference would
                // silently update the data (DB/state reads through it look
                // fine) without ever notifying the x-for effect, leaving
                // the DOM stuck on stale values.
                this.detectFields(this.files.at(-1));
            }
        },

        onDrop(event) {
            this.dragging = false;
            this.onFilesSelected(event.dataTransfer.files);
        },

        removeFile(id) {
            const entry = this.files.find((f) => f.id === id);
            if (entry?.previewUrl) URL.revokeObjectURL(entry.previewUrl);
            this.files = this.files.filter((f) => f.id !== id);
        },

        /**
         * Compressed exactly once per file and reused for both detection and the real upload, so their server-side content hashes match and OCR only runs once.
         * Scan types (X-Ray/Sonography/MRI-CT) skip compression entirely — the 2200px/JPEG-0.87 re-encode is tuned for photos of paper documents and would needlessly degrade diagnostic image detail before a vision model ever sees it.
         */
        getUploadBlob(entry) {
            const SCAN_TYPES = ['xray', 'sonography', 'mri_ct'];
            if (!entry.blobPromise) {
                const skipCompression = entry.isDocument || SCAN_TYPES.includes(entry.type);
                entry.blobPromise = skipCompression ? Promise.resolve(entry.file) : compressImage(entry.file);
            }

            return entry.blobPromise;
        },

        /** Best-effort — a failed or slow detection just leaves the form's normal defaults in place. */
        async detectFields(entry) {
            try {
                const blob = await this.getUploadBlob(entry);
                const form = new FormData();
                form.append('family_member_id', familyMemberId);
                form.append('file', blob, entry.name);

                const response = await fetch(detectUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body: form,
                });
                const json = await response.json();
                const detected = json.detected || {};

                if (detected.type && !entry.touched.type) entry.type = detected.type;
                if (detected.report_date && !entry.touched.reportDate) entry.reportDate = detected.report_date;
                if (detected.hospital_or_clinic_name && !entry.touched.hospital) entry.hospital = detected.hospital_or_clinic_name;
                if (detected.doctor_name && !entry.touched.doctor) entry.doctor = detected.doctor_name;

                entry.ocrPreview = json.text_preview || '';
                entry.ocrMethod = json.method || null;
            } catch (e) {
                // Detection is a nice-to-have; the form is still fully usable manually.
            } finally {
                entry.detecting = false;
            }
        },

        async uploadOne(entry, force = false) {
            entry.status = 'uploading';
            entry.error = null;

            const uploadBlob = await this.getUploadBlob(entry);

            return new Promise((resolve) => {
                const form = new FormData();
                form.append('family_member_id', familyMemberId);
                form.append('file', uploadBlob, entry.name);
                form.append('type', entry.type);
                form.append('report_date', entry.reportDate);
                form.append('hospital_or_clinic_name', entry.hospital);
                form.append('doctor_name', entry.doctor);
                if (force) form.append('force', '1');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl);
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) entry.progress = Math.round((e.loaded / e.total) * 100);
                };
                xhr.onload = () => {
                    let json = {};
                    try { json = JSON.parse(xhr.responseText); } catch (e) { /* fall through to generic error */ }

                    if (xhr.status >= 200 && xhr.status < 300 && json.success) {
                        entry.status = 'done';
                        entry.progress = 100;
                        entry.redirectUrl = json.redirect;
                    } else if (json.duplicate) {
                        entry.status = 'duplicate';
                        entry.error = json.message;
                        entry.existingReportUrl = json.existing_report_url;
                    } else {
                        entry.status = 'error';
                        entry.error = json.message || Object.values(json.errors || {})[0]?.[0] || 'Upload failed — please try again.';
                    }
                    resolve();
                };
                xhr.onerror = () => {
                    entry.status = 'error';
                    entry.error = 'Network error — please try again.';
                    resolve();
                };
                xhr.send(form);
            });
        },

        async uploadAnyway(entry) {
            await this.uploadOne(entry, true);
            this.redirectIfAllDone();
        },

        get canSubmit() {
            return this.files.length > 0 && !this.submitting && this.files.every((f) => f.type && f.reportDate);
        },

        redirectIfAllDone() {
            const allDone = this.files.length > 0 && this.files.every((f) => f.status === 'done');
            if (!allDone) return;

            window.location = this.files.length === 1 ? this.files[0].redirectUrl : reportsIndexUrl;
        },

        async submitAll() {
            if (!this.canSubmit) return;
            this.submitting = true;

            const pending = this.files.filter((f) => f.status === 'draft' || f.status === 'error');
            await Promise.all(pending.map((f) => this.uploadOne(f)));

            this.submitting = false;
            this.redirectIfAllDone();
        },
    };
}
