@props([
    'uploadUrl',
    'existingPreviewUrl' => null,
    'elementId' => 'novix-camera-capture',
])

{{-- Plain id (not x-ref) so callers outside this component's own Alpine
     scope — e.g. the registration wizard — can reach its data via
     Alpine.$data(document.getElementById(...)) without depending on
     Alpine's ref-scoping rules, which stop at nested x-data boundaries. --}}
<div
    id="{{ $elementId }}"
    x-data="cameraCapture({ uploadUrl: @js($uploadUrl), existingPreviewUrl: @js($existingPreviewUrl), csrfToken: @js(csrf_token()) })"
    class="text-center"
>
    <div class="relative mx-auto flex h-64 w-64 items-center justify-center overflow-hidden rounded-full bg-novix-ink/90 shadow-novix">
        <video x-ref="video" x-show="cameraStarted && !hasCaptured && !cameraError" autoplay playsinline muted class="h-full w-full scale-x-[-1] object-cover"></video>

        <img x-show="hasCaptured && capturedDataUrl" :src="capturedDataUrl" class="h-full w-full object-cover" alt="Captured photo preview">

        {{-- Nothing is requested yet here — the browser's camera-permission
             prompt only fires once startCamera() actually runs, from the
             "Turn on camera" button below. --}}
        <div x-show="!cameraStarted && !hasCaptured && !cameraError" x-cloak class="flex flex-col items-center gap-2 px-6 text-center text-sm text-white/70">
            <svg class="h-9 w-9 text-white/50" viewBox="0 0 24 24" fill="none"><path d="M3 7h4l2-2h6l2 2h4v12H3V7Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="12" cy="13" r="3.5" stroke="currentColor" stroke-width="1.6"/></svg>
            <span>Camera is off</span>
        </div>

        <div x-show="cameraStarted && !hasCaptured && !cameraError" x-cloak class="pointer-events-none absolute inset-4 rounded-full border-4 border-dashed border-white/50"></div>

        <div x-show="cameraError && !hasCaptured" x-cloak class="flex flex-col items-center gap-2 px-6 text-center text-sm text-white/80">
            <svg class="h-8 w-8 text-white/60" viewBox="0 0 24 24" fill="none"><path d="M3 7h4l2-2h6l2 2h4v12H3V7Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3 3l18 18" stroke="currentColor" stroke-width="1.6"/></svg>
            <span x-text="cameraError"></span>
        </div>

        <div x-show="justSaved" x-cloak x-transition class="absolute inset-0 flex items-center justify-center bg-novix-green/80">
            <svg class="h-14 w-14 text-white" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
        <button x-show="!cameraStarted && !hasCaptured && !cameraError" x-cloak type="button" @click="startCamera()"
            class="rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark">
            Turn on camera
        </button>

        <button x-show="cameraStarted && !hasCaptured && !cameraError" x-cloak type="button" @click="capture()"
            class="rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark">
            Capture
        </button>

        <button x-show="hasCaptured" x-cloak type="button" @click="retake()"
            class="rounded-xl border border-novix-green/30 bg-white px-6 py-2.5 text-sm font-semibold text-novix-green transition hover:bg-novix-mint/40">
            Retake
        </button>

        <label class="cursor-pointer rounded-xl border border-gray-200 bg-white px-6 py-2.5 text-sm font-semibold text-novix-ink transition hover:bg-novix-cream">
            <span x-text="hasCaptured ? 'Upload different photo' : 'Upload a photo instead'"></span>
            <input type="file" accept="image/*" class="hidden" @change="onFileSelected($event)">
        </label>
    </div>

    <p x-cloak x-show="uploadError" x-text="uploadError" class="mt-3 text-sm text-novix-pink-dark"></p>
</div>
