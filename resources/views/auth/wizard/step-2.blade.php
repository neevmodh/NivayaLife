@php
    $existingPreview = $hasPhoto ? route('register.photo-preview') : null;
@endphp

<div x-ref="step2">
    <h2 class="text-xl font-bold text-novix-ink dark:text-white">Add your photo <span class="text-sm font-normal text-novix-muted">(optional)</span></h2>
    <p class="mt-1 text-sm text-novix-muted">Used on your emergency ID card. You can skip this and add it later from your profile.</p>

    <div class="mt-6">
        <x-camera-capture :upload-url="route('register.step2')" :existing-preview-url="$existingPreview" />
    </div>

    <p x-cloak x-show="errorFor('photo')" x-text="errorFor('photo')" class="mt-4 text-center text-sm text-novix-pink-dark"></p>
</div>
