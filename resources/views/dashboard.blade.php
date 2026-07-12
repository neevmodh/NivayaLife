@php
    $reportTypeIcons = [
        'blood_test' => '&#129530;', 'prescription' => '&#128138;', 'xray' => '&#129504;',
        'mri_ct' => '&#129504;', 'insurance' => '&#128196;', 'bill' => '&#129530;',
        'ecg' => '&#128147;', 'other' => '&#128196;',
    ];
    $ocrBadge = [
        'pending' => ['label' => 'Pending', 'class' => 'bg-gray-100 text-gray-500'],
        'processing' => ['label' => 'Processing', 'class' => 'bg-novix-yellow/30 text-novix-yellow'],
        'completed' => ['label' => 'Completed', 'class' => 'bg-novix-mint text-novix-green'],
        'failed' => ['label' => 'Failed', 'class' => 'bg-novix-pink/30 text-novix-pink-dark'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Dashboard</h2>
    </x-slot>

    @if(session('just_registered'))
        <div x-data x-init="document.dispatchEvent(new CustomEvent('novix:confetti'))"></div>
        <x-confetti />
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
            class="fixed right-6 top-6 z-50 rounded-xl bg-novix-green px-5 py-3 text-sm font-semibold text-white shadow-novix">
            Welcome, {{ session('just_registered') }}! Your account is ready.
        </div>
    @endif

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        {{-- Hero card --}}
        <div class="overflow-hidden rounded-novix bg-novix-green shadow-novix">
            <div class="flex flex-col items-center gap-6 p-6 text-white sm:flex-row sm:items-center sm:p-8">
                @if($active->photo_path)
                    <img src="{{ Storage::url($active->photo_path) }}" class="h-24 w-24 flex-shrink-0 rounded-full border-4 border-white/20 object-cover" alt="{{ $active->full_name }}">
                @else
                    <span class="flex h-24 w-24 flex-shrink-0 items-center justify-center rounded-full border-4 border-white/20 bg-white/10 text-3xl font-bold">{{ strtoupper(substr($active->full_name, 0, 1)) }}</span>
                @endif

                <div class="flex-1 text-center sm:text-left">
                    <h1 class="text-2xl font-bold">{{ $active->full_name }}</h1>
                    <p class="mt-1 text-sm text-white/70">{{ $active->unique_health_id }} &middot; {{ Str::headline($active->relation) }}</p>
                    <div class="mt-3 flex flex-wrap justify-center gap-2 sm:justify-start">
                        @if($active->date_of_birth)
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">{{ $active->date_of_birth->age }} yrs</span>
                        @endif
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold capitalize">{{ $active->gender ? str_replace('_', ' ', $active->gender) : 'Not set' }}</span>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-1 rounded-2xl bg-white/10 px-6 py-4">
                    <span class="text-xs font-semibold uppercase tracking-wide text-white/70">Blood group</span>
                    <span class="text-3xl font-extrabold">{{ $active->blood_group ?? '—' }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- BMI gauge --}}
            <div class="rounded-novix bg-white p-6 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">BMI</h3>
                @if($latestBmi)
                    <div class="mt-4">
                        <x-bmi-gauge :height-cm="$latestBmi->height_cm" :weight-kg="$latestBmi->weight_kg" :editable="false" :trend="$trend" :size="180" />
                    </div>
                    <p class="mt-2 text-center text-xs text-novix-muted">Last recorded {{ $latestBmi->recorded_date->diffForHumans() }}</p>
                @else
                    <p class="mt-6 text-center text-sm text-novix-muted">No BMI recorded yet.</p>
                @endif
            </div>

            {{-- Quick actions --}}
            <div class="lg:col-span-2">
                <h3 class="mb-3 text-sm font-bold text-novix-ink dark:text-white">Quick actions</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <a href="{{ route('reports.upload') }}" class="flex flex-col items-center gap-2 rounded-novix bg-white p-5 text-center shadow-novix-sm transition hover:shadow-novix dark:bg-white/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-novix-mint text-novix-green"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 4v12m0-12 4 4m-4-4-4 4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span class="text-xs font-semibold text-novix-ink dark:text-white">Upload Report</span>
                    </a>
                    <a href="{{ route('timeline') }}" class="flex flex-col items-center gap-2 rounded-novix bg-white p-5 text-center shadow-novix-sm transition hover:shadow-novix dark:bg-white/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-novix-blue/20 text-novix-blue"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h10M4 18h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                        <span class="text-xs font-semibold text-novix-ink dark:text-white">View Timeline</span>
                    </a>
                    <a href="{{ route('id-card.show') }}" class="flex flex-col items-center gap-2 rounded-novix bg-white p-5 text-center shadow-novix-sm transition hover:shadow-novix dark:bg-white/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-novix-pink/25 text-novix-pink-dark"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M3 7h18v10H3V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="8" cy="12" r="1.5" fill="currentColor"/><path d="M13 10h5M13 14h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>
                        <span class="text-xs font-semibold text-novix-ink dark:text-white">Emergency Card</span>
                    </a>
                    <a href="{{ route('share.index') }}" class="flex flex-col items-center gap-2 rounded-novix bg-white p-5 text-center shadow-novix-sm transition hover:shadow-novix dark:bg-white/5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-novix-yellow/30 text-novix-yellow"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M18 8a3 3 0 1 0-2.83-4H15a3 3 0 0 0 .09 4.26L8.9 11.7a3 3 0 1 0 0 4.6l6.19 3.44A3 3 0 1 0 15 17.7l-6.19-3.44a3 3 0 0 0 0-.52L15 10.3c.52.44 1.19.7 1.91.7A3 3 0 0 0 18 8Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg></span>
                        <span class="text-xs font-semibold text-novix-ink dark:text-white">Share Report</span>
                    </a>
                </div>

                {{-- Family overview --}}
                <h3 class="mb-3 mt-6 text-sm font-bold text-novix-ink dark:text-white">Family</h3>
                <div class="flex flex-wrap gap-4 rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                    @foreach($familyMembers as $member)
                        <form method="POST" action="{{ route('dashboard.switch', $member) }}" class="flex flex-col items-center gap-1.5">
                            @csrf
                            <button type="submit" class="relative">
                                @if($member->photo_path)
                                    <img src="{{ Storage::url($member->photo_path) }}" class="h-14 w-14 rounded-full object-cover {{ $member->id === $active->id ? 'ring-4 ring-novix-green' : 'ring-2 ring-gray-100' }}" alt="{{ $member->full_name }}">
                                @else
                                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-novix-mint text-lg font-bold text-novix-green {{ $member->id === $active->id ? 'ring-4 ring-novix-green' : 'ring-2 ring-gray-100' }}">{{ strtoupper(substr($member->full_name, 0, 1)) }}</span>
                                @endif
                            </button>
                            <span class="text-xs font-medium text-novix-ink dark:text-white">{{ Str::of($member->full_name)->words(1, '') }}</span>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Recent reports --}}
            <div class="rounded-novix bg-white p-6 shadow-novix-sm dark:bg-white/5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-novix-ink dark:text-white">Recent reports</h3>
                    <a href="{{ route('reports.upload') }}" class="text-xs font-semibold text-novix-green hover:underline">Upload new</a>
                </div>

                @if($recentReports->isEmpty())
                    <p class="mt-6 text-center text-sm text-novix-muted">No reports uploaded yet.</p>
                @else
                    <ul class="mt-4 divide-y divide-gray-100 dark:divide-white/10">
                        @foreach($recentReports as $report)
                            @php($badge = $ocrBadge[$report->ocr_status] ?? $ocrBadge['pending'])
                            <li class="flex items-center gap-3 py-3">
                                <span class="text-xl">{!! $reportTypeIcons[$report->type] ?? '&#128196;' !!}</span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-novix-ink dark:text-white">{{ Str::headline($report->type) }}</p>
                                    <p class="text-xs text-novix-muted">{{ $report->report_date?->format('M j, Y') ?? $report->uploaded_at?->format('M j, Y') }}</p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Upcoming medications --}}
            <div class="rounded-novix bg-white p-6 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Today's medications</h3>

                @if($activeMedications->isEmpty())
                    <p class="mt-6 text-center text-sm text-novix-muted">No active medications.</p>
                @else
                    <ul class="mt-4 divide-y divide-gray-100 dark:divide-white/10">
                        @foreach($activeMedications as $medication)
                            <li class="py-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-novix-ink dark:text-white">{{ $medication->medicine_name }}</p>
                                    <p class="text-xs text-novix-muted">{{ $medication->dosage }}</p>
                                </div>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @forelse($medication->schedule_times ?? [] as $time)
                                        @php($log = $medication->medicationLogs->first(fn($l) => str($l->scheduled_at->format('H:i'))->exactly($time)))
                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $log?->status === 'taken' ? 'bg-novix-mint text-novix-green' : ($log?->status === 'missed' ? 'bg-novix-pink/30 text-novix-pink-dark' : 'bg-gray-100 text-gray-500') }}">{{ $time }}</span>
                                    @empty
                                        <span class="text-xs text-novix-muted">{{ $medication->frequency ?? 'As needed' }}</span>
                                    @endforelse
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
