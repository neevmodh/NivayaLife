<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Deployments</h2>
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                Back to overview
            </a>
        </div>
    </x-slot>

    @php
        $badgeClasses = [
            'pending' => 'bg-novix-yellow/30 text-novix-ink dark:bg-novix-yellow/20 dark:text-novix-yellow',
            'failed' => 'bg-novix-pink/20 text-novix-pink-dark',
            'deployed' => 'bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint',
            'rejected' => 'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-300',
            'superseded' => 'bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500',
            'approved' => 'bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint',
        ];
    @endphp

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        @if(session('admin_status'))
            <div class="mb-4 rounded-xl bg-novix-mint px-4 py-3 text-sm font-semibold text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">{{ session('admin_status') }}</div>
        @endif
        @if(session('admin_error'))
            <div class="mb-4 rounded-xl bg-novix-pink/20 px-4 py-3 text-sm font-semibold text-novix-pink-dark">{{ session('admin_error') }}</div>
        @endif

        <p class="mb-4 text-sm text-novix-muted">
            Approving deploys whatever is currently the latest commit on <code>main</code> — if a newer commit has landed
            since a row was pushed, that's what actually gets deployed. Only the single most recent pending (or failed)
            commit can be approved or rejected.
        </p>

        <div class="space-y-3">
            @forelse($deployments as $deployment)
                <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $badgeClasses[$deployment->status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ ucfirst($deployment->status) }}
                                </span>
                                <span class="font-mono text-xs text-novix-muted">{{ substr($deployment->commit_sha, 0, 7) }}</span>
                                <span class="text-xs text-novix-muted">{{ $deployment->pushed_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-2 truncate text-sm font-semibold text-novix-ink dark:text-white">{{ $deployment->commit_message }}</p>
                            <p class="mt-1 text-xs text-novix-muted">
                                {{ $deployment->author_name }}
                                @if($deployment->reviewer)
                                    &middot; reviewed by {{ $deployment->reviewer->name }}
                                @endif
                            </p>
                        </div>

                        @if($deployment->id === $actionableId)
                            <div class="flex shrink-0 gap-2">
                                <form method="POST" action="{{ route('admin.deployments.approve', $deployment) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-novix-green px-4 py-2 text-sm font-semibold text-white hover:bg-novix-green/90">
                                        Approve
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.deployments.reject', $deployment) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-novix-ink hover:bg-novix-cream dark:border-white/10 dark:text-white dark:hover:bg-white/10">
                                        Reject
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-novix-muted">No deploys recorded yet.</p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $deployments->links() }}
        </div>
    </div>
</x-app-layout>
