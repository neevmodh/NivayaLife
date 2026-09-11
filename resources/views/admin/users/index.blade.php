<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-nivayalife-ink dark:text-white">Users</h2>
                <span class="mt-1.5 block h-0.5 w-10 rounded-full bg-nivayalife-gold" aria-hidden="true"></span>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-nivayalife-ink transition hover:-translate-y-0.5 hover:bg-nivayalife-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Back to overview
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-nivayalife-muted" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <input type="text" name="q" value="{{ $search }}" placeholder="Search by name or email&hellip;"
                    class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-3 text-sm transition focus:border-nivayalife-green focus:outline-none focus:ring-2 focus:ring-nivayalife-green/30 dark:border-white/10 dark:bg-white/5 dark:text-white">
            </div>
        </form>

        @if($users->isEmpty())
            <div class="rounded-nivayalife bg-white p-10 text-center shadow-nivayalife-sm dark:bg-white/5">
                <p class="text-sm text-nivayalife-muted">No users match that search.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($users as $user)
                    <a href="{{ route('admin.users.show', $user) }}"
                        class="nivayalife-gold-edge flex items-center gap-4 rounded-nivayalife bg-white p-4 shadow-nivayalife-sm transition hover:-translate-y-0.5 hover:shadow-nivayalife dark:bg-white/5">
                        <x-avatar :photo-path="$user->avatar_path" :full-name="$user->name" size="h-10 w-10" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-sm font-bold text-nivayalife-ink dark:text-white">{{ $user->name }}</p>
                                @if($user->is_admin)
                                    <span class="rounded-full bg-nivayalife-gold/20 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:text-nivayalife-gold">Admin</span>
                                @endif
                                @unless($user->email_verified_at)
                                    <span class="rounded-full bg-nivayalife-yellow/30 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:text-nivayalife-yellow">Unverified</span>
                                @endunless
                            </div>
                            <p class="truncate text-xs text-nivayalife-muted">{{ $user->email }}</p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="text-sm font-bold text-nivayalife-ink dark:text-white">{{ $user->family_members_count }}</p>
                            <p class="text-[11px] text-nivayalife-muted">members</p>
                        </div>
                        <svg class="h-4 w-4 flex-shrink-0 text-nivayalife-muted" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">{{ $users->links() }}</div>
        @endif
    </div>
</x-app-layout>
