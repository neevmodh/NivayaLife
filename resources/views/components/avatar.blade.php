@props([
    'photoPath' => null,
    'fullName' => '',
    'gender' => null,
    'age' => null,
    'size' => 'h-12 w-12',
    'rounded' => 'rounded-full',
    'colorClass' => 'bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint',
])

@php
    // Two age buckets (child < 13, adult otherwise/unknown) crossed with
    // gender give 5 icons total — anything other than male/female (or no
    // gender/age on file, e.g. a linked User rather than a FamilyMember)
    // falls back to the neutral silhouette rather than guessing.
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

@if($photoPath)
    <img src="{{ Storage::url($photoPath) }}" {{ $attributes->merge(['class' => "{$size} {$rounded} object-cover"]) }} alt="{{ $fullName }}">
@else
    <span {{ $attributes->merge(['class' => "{$size} {$rounded} {$colorClass} flex items-center justify-center"]) }}>
        <svg class="h-3/5 w-3/5" viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $path !!}</svg>
    </span>
@endif
