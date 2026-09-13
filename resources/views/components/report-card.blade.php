{{-- Shared card shell for the structured-report partials (key-values, medicines, imaging). --}}
<div {{ $attributes->merge(['class' => 'mt-6 rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5']) }}>
    {{ $slot }}
</div>
