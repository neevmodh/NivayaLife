@php
    $formatBytes = function (?int $bytes) {
        $bytes = $bytes ?? 0;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2).' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2).' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2).' KB';
        return $bytes.' B';
    };

    $statusTone = [
        'completed' => 'bg-nivayalife-mint text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint',
        'failed' => 'bg-nivayalife-pink/30 text-nivayalife-pink-dark',
        'processing' => 'bg-nivayalife-yellow/30 text-amber-700 dark:text-nivayalife-yellow',
        'pending' => 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-white/60',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <x-avatar :photo-path="$user->avatar_path" :full-name="$user->name" size="h-12 w-12" />
                <div class="min-w-0">
                    <h2 class="truncate text-2xl font-bold leading-tight tracking-tight text-nivayalife-ink dark:text-white">{{ $user->name }}</h2>
                    <p class="truncate text-sm text-nivayalife-muted">{{ $user->email }}</p>
                </div>
            </div>
            <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-nivayalife-ink transition hover:-translate-y-0.5 hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                All users
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Family members', 'value' => $members->count(), 'sub' => 'linked to this account'],
                ['label' => 'Reports', 'value' => number_format($reportCount), 'sub' => $formatBytes($storageBytes).' stored'],
                ['label' => 'Assistant messages', 'value' => number_format($chatStats->messages ?? 0), 'sub' => number_format($chatStats->tokens ?? 0).' tokens'],
                ['label' => 'Joined', 'value' => $user->created_at->format('M Y'), 'sub' => $user->created_at->diffForHumans()],
            ] as $stat)
                <div class="rounded-nivayalife border-t-2 border-nivayalife-gold/50 bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-nivayalife-muted">{{ $stat['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold text-nivayalife-ink dark:text-white">{{ $stat['value'] }}</p>
                    <p class="mt-1 text-xs text-nivayalife-muted">{{ $stat['sub'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">Family members</h3>
                @if($members->isEmpty())
                    <p class="mt-3 text-sm text-nivayalife-muted">No family members on this account.</p>
                @else
                    <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
                        @foreach($members as $member)
                            <li class="flex items-center gap-3 py-2.5">
                                <x-avatar :photo-path="$member->photo_path" :preset="$member->avatar_preset ?? null" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()" size="h-8 w-8" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-nivayalife-ink dark:text-white">{{ $member->full_name }}</p>
                                    <p class="text-xs capitalize text-nivayalife-muted">{{ str_replace('_', ' ', $member->relation) }} &middot; {{ $member->status }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">Recent reports</h3>
                @if($reportsByStatus->isNotEmpty())
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($reportsByStatus as $status => $total)
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $statusTone[$status] ?? 'bg-gray-100 text-gray-500' }}">{{ Str::headline($status) }}: {{ $total }}</span>
                        @endforeach
                    </div>
                @endif
                @if($recentReports->isEmpty())
                    <p class="mt-3 text-sm text-nivayalife-muted">No reports uploaded.</p>
                @else
                    <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
                        @foreach($recentReports as $report)
                            <li class="flex items-center justify-between gap-3 py-2.5">
                                <p class="truncate text-sm text-nivayalife-ink dark:text-white">{{ $report->typeLabel() }}</p>
                                <span class="flex-shrink-0 text-xs text-nivayalife-muted">{{ $report->uploaded_at?->format('M j, Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">Recent sign-ins</h3>
                @if($recentLogins->isEmpty())
                    <p class="mt-3 text-sm text-nivayalife-muted">No sign-ins recorded.</p>
                @else
                    <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
                        @foreach($recentLogins as $login)
                            <li class="flex items-center justify-between gap-3 py-2.5">
                                <span class="flex items-center gap-2 text-xs">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $login->successful ? 'bg-nivayalife-green' : 'bg-nivayalife-pink-dark' }}"></span>
                                    <span class="font-mono text-nivayalife-muted">{{ $login->ip_address ?? '—' }}</span>
                                </span>
                                <span class="flex-shrink-0 text-xs text-nivayalife-muted">{{ $login->created_at->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-nivayalife-ink dark:text-white">Recent activity</h3>
                @if($recentActivity->isEmpty())
                    <p class="mt-3 text-sm text-nivayalife-muted">Nothing recorded.</p>
                @else
                    <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/10">
                        @foreach($recentActivity as $entry)
                            <li class="flex items-center justify-between gap-3 py-2.5">
                                <span class="truncate text-xs text-nivayalife-ink dark:text-white">{{ Str::headline($entry->action) }}</span>
                                <span class="flex-shrink-0 text-xs text-nivayalife-muted">{{ $entry->created_at->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
