@php
    $formatBytes = function (?int $bytes) {
        $bytes = $bytes ?? 0;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2).' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2).' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2).' KB';
        return $bytes.' B';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Admin overview</h2>
            <a href="{{ route('admin.tables') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Browse database tables
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        {{-- Top-line stats --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Users</p>
                <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ number_format($userCount) }}</p>
                <p class="mt-1 text-xs text-novix-muted">{{ $verifiedUserCount }} verified &middot; {{ $newUsersLast7Days }} new (7d)</p>
            </div>
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Family members</p>
                <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ number_format($familyMemberCount) }}</p>
                <p class="mt-1 text-xs text-novix-muted">
                    @foreach($familyMembersByAccessType as $type => $count)
                        {{ Str::headline($type) }}: {{ $count }}@if(!$loop->last) &middot; @endif
                    @endforeach
                </p>
            </div>
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Reports</p>
                <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ number_format($reportCount) }}</p>
                <p class="mt-1 text-xs text-novix-muted">{{ $formatBytes($totalStorageBytes) }} stored</p>
            </div>
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-novix-muted">Background jobs</p>
                <p class="mt-1 text-2xl font-bold text-novix-ink dark:text-white">{{ number_format($pendingJobCount) }}</p>
                <p class="mt-1 text-xs {{ $failedJobCount > 0 ? 'text-novix-pink-dark font-semibold' : 'text-novix-muted' }}">
                    {{ $failedJobCount }} failed
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Reports breakdown --}}
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Reports by type</h3>
                <div class="mt-3 space-y-2">
                    @forelse($reportsByType as $type => $count)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-novix-muted">{{ Str::headline($type) }}</span>
                            <span class="font-semibold text-novix-ink dark:text-white">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-novix-muted">No reports yet.</p>
                    @endforelse
                </div>

                <h3 class="mt-5 text-sm font-bold text-novix-ink dark:text-white">OCR status</h3>
                <div class="mt-3 space-y-2">
                    @forelse($reportsByOcrStatus as $status => $count)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-novix-muted">{{ Str::headline($status) }}</span>
                            <span class="font-semibold text-novix-ink dark:text-white">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-novix-muted">No reports yet.</p>
                    @endforelse
                </div>

                <h3 class="mt-5 text-sm font-bold text-novix-ink dark:text-white">AI jobs by status</h3>
                <div class="mt-3 space-y-2">
                    @forelse($aiJobsByStatus as $status => $count)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-novix-muted">{{ Str::headline($status) }}</span>
                            <span class="font-semibold text-novix-ink dark:text-white">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-novix-muted">No AI jobs yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Recent users --}}
            <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                <h3 class="text-sm font-bold text-novix-ink dark:text-white">Recent signups</h3>
                <div class="mt-3 space-y-3">
                    @forelse($recentUsers as $user)
                        <div class="flex items-center justify-between text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-novix-ink dark:text-white">{{ $user->name }}</p>
                                <p class="truncate text-xs text-novix-muted">{{ $user->email }}</p>
                            </div>
                            <span class="flex-shrink-0 text-xs text-novix-muted">{{ $user->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-novix-muted">No users yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent activity --}}
        <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
            <h3 class="text-sm font-bold text-novix-ink dark:text-white">Recent activity</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs font-semibold uppercase tracking-wide text-novix-muted">
                            <th class="pb-2 pr-4">When</th>
                            <th class="pb-2 pr-4">Who</th>
                            <th class="pb-2 pr-4">Action</th>
                            <th class="pb-2">Target</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @forelse($recentAuditLog as $entry)
                            <tr>
                                <td class="whitespace-nowrap py-2 pr-4 text-novix-muted">{{ $entry->created_at->diffForHumans() }}</td>
                                <td class="whitespace-nowrap py-2 pr-4">{{ $entry->user?->name ?? 'System' }}</td>
                                <td class="whitespace-nowrap py-2 pr-4 font-medium text-novix-ink dark:text-white">{{ Str::headline($entry->action) }}</td>
                                <td class="whitespace-nowrap py-2 text-novix-muted">{{ $entry->target_type }} #{{ $entry->target_id }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-3 text-novix-muted">No activity recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
