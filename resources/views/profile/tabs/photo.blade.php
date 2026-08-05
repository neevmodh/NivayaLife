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
                <x-avatar :photo-path="$member->photo_path" :preset="$member->avatar_preset ?? null" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()" size="h-40 w-40" class="shadow-novix-sm" />
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

    {{-- Illustrated avatars, for anyone who would rather not use a photo.
         A saved photo still takes priority, so this is offered as the
         alternative it is rather than silently doing nothing. --}}
    <div class="mt-10 border-t border-gray-100 pt-8 dark:border-white/10"
        x-data="{
            selected: @js($member->avatar_preset),
            saving: false,
            saved: false,
            async choose(key) {
                if (this.saving) return;
                const next = this.selected === key ? null : key;
                this.saving = true;
                try {
                    const res = await fetch(@js($isDependentEdit ? route('profile.avatar-preset.member', $member) : route('profile.avatar-preset')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': @js(csrf_token()),
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({ avatar_preset: next }),
                    });
                    if (!res.ok) throw new Error('save failed');
                    this.selected = next;
                    this.saved = true;
                    setTimeout(() => (this.saved = false), 1800);
                } catch (e) {
                    alert('Could not save that avatar. Please try again.');
                } finally {
                    this.saving = false;
                }
            },
        }">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-novix-ink dark:text-white">Or pick an avatar</h3>
                <p class="mt-1 text-sm text-novix-muted">
                    @if($member->photo_path)
                        Your photo is used wherever it exists — remove it to show an avatar instead.
                    @else
                        Used across the app in place of initials. Tap again to remove.
                    @endif
                </p>
            </div>
            <span x-cloak x-show="saved" class="flex-shrink-0 rounded-full bg-novix-mint px-3 py-1 text-xs font-bold text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">Saved</span>
        </div>

        {{-- Grouped by age so picking one for a grandparent or a baby is a
             matter of looking in the right row, not scanning sixteen faces. --}}
        @foreach(\App\Support\AvatarPresets::grouped() as $group => $presets)
            <div class="mt-5">
                <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-novix-muted">{{ $group }}</p>
                <div class="grid grid-cols-5 gap-3 sm:grid-cols-8">
                    @foreach($presets as $key => $data)
                        <button type="button" @click="choose(@js($key))" :disabled="saving"
                            class="group relative aspect-square rounded-full transition hover:-translate-y-0.5 active:scale-95 disabled:opacity-50"
                            :class="selected === @js($key) ? 'ring-2 ring-novix-green ring-offset-2 dark:ring-offset-novix-night' : 'ring-1 ring-gray-200 hover:ring-novix-green/40 dark:ring-white/10'"
                            :aria-pressed="(selected === @js($key)).toString()"
                            title="{{ $data['label'] }}" aria-label="{{ $data['label'] }} avatar">
                            <x-avatar :full-name="$member->full_name" :preset="$key" size="h-full w-full" />
                            <span x-cloak x-show="selected === @js($key)"
                                class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-novix-green text-white ring-2 ring-white dark:ring-novix-night" aria-hidden="true">
                                <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
