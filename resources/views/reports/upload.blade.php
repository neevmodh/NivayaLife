<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-nivayalife-ink dark:text-white">Upload Report</h2>
        <p class="mt-1 text-sm text-nivayalife-muted">For {{ $active->full_name }}</p>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8"
        x-data="reportUpload({
            familyMemberId: {{ $active->id }},
            uploadUrl: @js(route('reports.store')),
            detectUrl: @js(route('reports.detect')),
            csrfToken: @js(csrf_token()),
            reportsIndexUrl: @js(route('reports.index')),
        })"
    >
        <datalist id="doctor-suggestions">
            @foreach($doctorNames as $name)
                <option value="{{ $name }}"></option>
            @endforeach
        </datalist>

        {{-- Drop zone --}}
        <div
            class="rounded-nivayalife border-2 border-dashed p-10 text-center transition"
            :class="dragging ? 'border-nivayalife-green bg-nivayalife-mint/30' : 'border-gray-300 bg-white dark:border-white/15 dark:bg-white/5'"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop($event)"
        >
            <input type="file" id="report-file-input" class="hidden" multiple
                accept=".pdf,.jpg,.jpeg,.png,.webp,.tiff,.tif,.bmp,.gif,.docx,application/pdf,image/jpeg,image/png,image/webp,image/tiff,image/bmp,image/gif,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                @change="onFilesSelected($event.target.files); $event.target.value = ''">
            <input type="file" id="report-camera-input" class="hidden"
                accept="image/*" capture="environment"
                @change="onFilesSelected($event.target.files); $event.target.value = ''">

            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-nivayalife-mint text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint" aria-hidden="true">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none"><path d="M12 4v12m0-12 4 4m-4-4-4 4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>

            <p class="mt-4 text-sm font-semibold text-nivayalife-ink dark:text-white">Drag &amp; drop files here</p>
            <p class="mt-1 text-xs text-nivayalife-muted">or</p>

            <div class="mt-3 flex flex-wrap justify-center gap-3">
                <label for="report-file-input" class="cursor-pointer rounded-xl bg-nivayalife-green px-5 py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark">Choose Files</label>
                <label for="report-camera-input" class="cursor-pointer rounded-xl border-2 border-nivayalife-green px-5 py-2.5 text-sm font-semibold text-nivayalife-green transition hover:bg-nivayalife-mint/40 sm:hidden">Use Camera</label>
            </div>
            <p class="mt-3 text-xs text-nivayalife-muted">PDF, Word, or image (JPG/PNG/WEBP/TIFF/BMP/GIF) &middot; up to 10MB each &middot; multiple files supported</p>
        </div>

        {{-- File cards --}}
        <div class="mt-6 space-y-4" x-show="files.length > 0" x-cloak>
            <template x-for="f in files" :key="f.id">
                <div class="rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                    <div class="flex gap-4">
                        <div class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-xl bg-nivayalife-cream dark:bg-white/10">
                            <img x-show="!f.isDocument" :src="f.previewUrl" class="h-full w-full object-cover" alt="">
                            <div x-show="f.isDocument" class="flex h-full w-full items-center justify-center text-nivayalife-green dark:text-nivayalife-mint" aria-hidden="true">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m1 5H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l4.414 4.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-nivayalife-ink dark:text-white" x-text="f.name"></p>
                                <button type="button" @click="removeFile(f.id)" x-show="f.status !== 'uploading'" class="flex-shrink-0 text-nivayalife-muted hover:text-nivayalife-pink-dark" aria-label="Remove file">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                </button>
                            </div>

                            <div x-show="f.status === 'uploading'" class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                <div class="h-1.5 rounded-full bg-nivayalife-green transition-all" :style="`width:${f.progress}%`"></div>
                            </div>
                            <p x-show="f.status === 'done'" class="mt-1 flex items-center gap-1 text-xs font-semibold text-nivayalife-green">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Uploaded
                            </p>
                            <p x-show="f.status === 'error'" class="mt-1 text-xs font-semibold text-nivayalife-pink-dark" x-text="f.error"></p>

                            <div x-show="f.status === 'duplicate'" class="mt-1">
                                <p class="text-xs font-semibold text-nivayalife-yellow" x-text="f.error"></p>
                                <div class="mt-1.5 flex gap-3">
                                    <a :href="f.existingReportUrl" class="text-xs font-semibold text-nivayalife-green hover:underline">View existing report</a>
                                    <button type="button" @click="uploadAnyway(f)" class="text-xs font-semibold text-nivayalife-muted hover:underline">Upload anyway</button>
                                </div>
                            </div>

                            <div class="mt-3" x-show="f.status !== 'uploading' && f.status !== 'done' && f.status !== 'duplicate'">
                                <p x-show="f.detecting" class="mb-2 flex items-center gap-1.5 text-xs font-medium text-nivayalife-muted">
                                    <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                    Detecting report details&hellip;
                                </p>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="col-span-2">
                                        <p class="mb-1.5 text-xs font-semibold text-nivayalife-muted">Report type</p>
                                        <div class="grid grid-cols-4 gap-1.5">
                                            <template x-for="opt in typeOptions" :key="opt.value">
                                                <button type="button" @click="f.type = opt.value; f.touched.type = true"
                                                    :class="f.type === opt.value ? 'border-nivayalife-green bg-nivayalife-mint/50 text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint' : 'border-gray-200 text-nivayalife-muted hover:border-nivayalife-green/40 dark:border-white/10'"
                                                    class="flex flex-col items-center gap-1 rounded-lg border-2 px-1 py-2 text-[10px] font-semibold transition">
                                                    <span class="text-base" x-text="opt.icon"></span>
                                                    <span x-text="opt.label"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-nivayalife-muted">Report date</label>
                                        <input type="date" x-model="f.reportDate" @input.once="f.touched.reportDate = true" max="{{ now()->toDateString() }}" class="w-full rounded-lg border border-gray-200 bg-nivayalife-cream/40 px-3 py-2 text-sm text-nivayalife-ink focus:border-nivayalife-green focus:outline-none focus:ring-2 focus:ring-nivayalife-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-nivayalife-muted">Hospital/Clinic</label>
                                        <input type="text" x-model="f.hospital" @input.once="f.touched.hospital = true" placeholder="Optional" class="w-full rounded-lg border border-gray-200 bg-nivayalife-cream/40 px-3 py-2 text-sm text-nivayalife-ink focus:border-nivayalife-green focus:outline-none focus:ring-2 focus:ring-nivayalife-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="mb-1 block text-xs font-semibold text-nivayalife-muted">Doctor name</label>
                                        <input type="text" x-model="f.doctor" @input.once="f.touched.doctor = true" list="doctor-suggestions" placeholder="Optional &mdash; start typing" class="w-full rounded-lg border border-gray-200 bg-nivayalife-cream/40 px-3 py-2 text-sm text-nivayalife-ink focus:border-nivayalife-green focus:outline-none focus:ring-2 focus:ring-nivayalife-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
                                    </div>
                                </div>

                                <div class="mt-3" x-show="f.ocrPreview" x-cloak>
                                    <button type="button" @click="f.showOcrPreview = !f.showOcrPreview" class="flex items-center gap-1 text-xs font-semibold text-nivayalife-green hover:underline">
                                        <svg class="h-3 w-3 transition-transform" :class="f.showOcrPreview ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <span x-text="f.showOcrPreview ? 'Hide extracted text' : 'Preview extracted text'"></span>
                                        <span class="text-nivayalife-muted font-normal" x-text="f.ocrMethod === 'paddleocr' ? '(PaddleOCR)' : f.ocrMethod === 'tesseract' ? '(Tesseract)' : ''"></span>
                                    </button>
                                    <pre x-show="f.showOcrPreview" x-cloak class="mt-1.5 max-h-32 overflow-y-auto whitespace-pre-wrap rounded-lg bg-nivayalife-cream/60 p-2.5 font-mono text-[11px] leading-snug text-nivayalife-ink dark:bg-white/5 dark:text-white/80" x-text="f.ocrPreview"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-6 flex justify-end" x-show="files.length > 0" x-cloak>
            <button type="button" @click="submitAll()" :disabled="!canSubmit"
                class="rounded-xl bg-nivayalife-green px-6 py-3 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:bg-nivayalife-green-dark disabled:cursor-not-allowed disabled:opacity-40">
                <span x-show="!submitting" x-text="`Upload ${files.length} file${files.length === 1 ? '' : 's'}`"></span>
                <span x-show="submitting">Uploading&hellip;</span>
            </button>
        </div>
    </div>
</x-app-layout>
