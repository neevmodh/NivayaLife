@props([
    'photoPath' => null,
    'fullName' => '',
    'gender' => null,
    'age' => null,
    'preset' => null,
    'size' => 'h-12 w-12',
    'rounded' => 'rounded-full',
    'colorClass' => 'bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint',
])

@php
    use App\Support\AvatarPresets;

    // Order matters: a real photo always wins, then a chosen illustration,
    // then coloured initials. The old line-art silhouette is the last resort
    // — a family with no photos used to render as identical grey shapes.
    $presetData = AvatarPresets::has($preset) ? AvatarPresets::all()[$preset] : null;
    $hasName = trim($fullName) !== '';
@endphp

@if($photoPath)
    <img src="{{ Storage::url($photoPath) }}" {{ $attributes->merge(['class' => "{$size} {$rounded} object-cover"]) }} alt="{{ $fullName }}">

@elseif($presetData)
    <span {{ $attributes->merge(['class' => "{$size} {$rounded} overflow-hidden flex items-center justify-center"]) }}
        role="img" aria-label="{{ $fullName }}">
        <svg viewBox="0 0 48 48" class="h-full w-full" aria-hidden="true">
            <rect width="48" height="48" fill="{{ $presetData['bg'] }}"/>
            {{-- Shoulders, then head, then hair on top. --}}
            <path d="M8 48c0-8.5 7.2-13 16-13s16 4.5 16 13Z" fill="{{ $presetData['cloth'] }}"/>
            <circle cx="24" cy="22" r="9" fill="{{ $presetData['skin'] }}"/>
            <circle cx="20.8" cy="21.5" r="1.15" fill="#2C2016"/>
            <circle cx="27.2" cy="21.5" r="1.15" fill="#2C2016"/>
            <path d="M21.6 25.6c1.4 1.2 3.4 1.2 4.8 0" stroke="#2C2016" stroke-width="1.3" stroke-linecap="round" fill="none"/>
            {!! $presetData['hair'] !!}
        </svg>
    </span>

@elseif($hasName)
    @php
        $c = AvatarPresets::initialsColor($fullName);
    @endphp
    <span {{ $attributes->merge(['class' => "{$size} {$rounded} flex items-center justify-center font-bold leading-none"]) }}
        style="background-color: {{ $c['bg'] }}; color: {{ $c['fg'] }};"
        role="img" aria-label="{{ $fullName }}">
        {{-- Sized off the container so one component works from h-7 to h-40. --}}
        <span style="font-size: 38%;">{{ AvatarPresets::initials($fullName) }}</span>
    </span>

@else
    @php
        // No name either (e.g. a bare User row) — fall back to the neutral
        // silhouette rather than rendering an empty circle.
        $bucket = ($age !== null && $age < 13) ? 'child' : 'adult';
        $genderKey = in_array($gender, ['male', 'female'], true) ? $gender : 'neutral';

        $icons = [
            'adult_male' => '<circle cx="12" cy="7.5" r="3.5" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M4.5 21v-1.5A6.5 6.5 0 0 1 11 13h2a6.5 6.5 0 0 1 6.5 6.5V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
            'adult_female' => '<circle cx="12" cy="7.5" r="3.5" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M8 13.3 5 21h14l-3-7.7M9.5 13h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
            'child_male' => '<circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M6.5 21v-1.2A5.5 5.5 0 0 1 12 14.3a5.5 5.5 0 0 1 5.5 5.5V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
            'child_female' => '<circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M9 14.6 6.8 21h10.4l-2.2-6.4M10 14.3h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
            'neutral' => '<circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6" fill="none"/><path d="M5 21v-1a7 7 0 0 1 14 0v1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
        ];

        $key = $genderKey === 'neutral' ? 'neutral' : "{$bucket}_{$genderKey}";
        $path = $icons[$key] ?? $icons['neutral'];
    @endphp
    <span {{ $attributes->merge(['class' => "{$size} {$rounded} {$colorClass} flex items-center justify-center"]) }}>
        <svg class="h-3/5 w-3/5" viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $path !!}</svg>
    </span>
@endif
