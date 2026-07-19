@php
    $currentPhotoUrl = $member->photo_path ? Storage::url($member->photo_path) : null;
@endphp

<div>
    <h3 class="text-lg font-bold text-novix-ink dark:text-white">Photo</h3>
    <p class="mt-1 text-sm text-novix-muted">
        {{ $isDependentEdit ? "Used on {$member->full_name}'s emergency ID card — retake or upload a new one any time." : 'Used on your emergency ID card — retake or upload a new one any time.' }}
    </p>

    <div class="mt-6" x-data="{ retaking: false }">
        <template x-if="!retaking">
            <div class="flex flex-col items-center gap-4">
                <x-avatar :photo-path="$member->photo_path" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()" size="h-40 w-40" class="shadow-novix-sm" />
                <button type="button" @click="retaking = true" class="rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm hover:bg-novix-green-dark">
                    {{ $isDependentEdit ? 'Retake or upload a new photo' : 'Retake selfie / upload new photo' }}
                </button>
            </div>
        </template>

        <template x-if="retaking">
            <div x-data="{ done: false }">
                <div x-show="!done">
                    <x-camera-capture :upload-url="$isDependentEdit ? route('profile.photo.member', $member) : route('profile.photo')" :existing-preview-url="$currentPhotoUrl" element-id="novix-profile-camera" />
                    <div class="mt-5 flex justify-center">
                        <button type="button" @click="
                            const cam = window.Alpine.$data(document.getElementById('novix-profile-camera'));
                            cam.novixUpload().then(ok => { if (ok) { done = true; setTimeout(() => window.location.reload(), 1200); } });
                        " class="rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm hover:bg-novix-green-dark">
                            Save photo
                        </button>
                    </div>
                </div>
                <div x-show="done" x-cloak class="flex flex-col items-center gap-2 py-8 text-novix-green">
                    <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="text-sm font-semibold">Photo updated</span>
                </div>
            </div>
        </template>
    </div>
</div>
