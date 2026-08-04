@php
    $accessBadge = fn ($member) => $member->access_type === 'linked'
        ? ['label' => 'Linked', 'class' => 'bg-novix-blue/20 text-novix-blue']
        : ['label' => 'Dependent', 'class' => 'bg-novix-mint text-novix-green dark:bg-novix-green/20 dark:text-novix-mint'];
    $isReciprocal = fn ($member) => $member->primary_account_id !== auth()->id() && $member->linked_user_id !== auth()->id();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">Family</h2>
                <p class="mt-1 text-sm text-novix-muted">Everyone linked to your Nivaya Life account.</p>
            </div>
            <a href="{{ route('family.add') }}"
                class="flex items-center gap-2 rounded-xl bg-novix-green px-5 py-2.5 text-sm font-semibold text-white shadow-novix-sm transition hover:bg-novix-green-dark">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                Add Family Member
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">

        @if(session('status'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                class="rounded-xl bg-novix-mint px-4 py-3 text-sm font-semibold text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">
                @switch(session('status'))
                    @case('family-member-archived') That family member has been archived. Their medical history is kept, not deleted. @break
                    @case('family-member-restored') Family member restored. @break
                    @case('invite-sent') Invitation sent! @break
                    @case('invite-resent') Invitation resent. @break
                    @case('invite-cancelled') Invitation cancelled. @break
                    @case('dependent-added') Family member added. @break
                    @case('sharing-revoked') Access revoked. They'll no longer be able to view your profile. @break
                    @default {{ session('status') }}
                @endswitch
            </div>
        @endif

        {{-- Active --}}
        <section>
            <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-novix-ink dark:text-white">
                <span class="h-2 w-2 rounded-full bg-novix-green"></span>
                Active ({{ $active->count() }})
            </h3>

            @if($active->isEmpty())
                <p class="text-sm text-novix-muted">No active family members yet.</p>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($active as $member)
                        @php($badge = $accessBadge($member))
                        @php($reciprocal = $isReciprocal($member))
                        <div class="rounded-novix bg-white p-5 shadow-novix-sm transition hover:shadow-novix dark:bg-white/5 {{ $reciprocal ? 'border border-novix-blue/20' : '' }}">
                            <div class="flex items-start gap-3">
                                <x-avatar :photo-path="$member->photo_path" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()" size="h-12 w-12" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-novix-ink dark:text-white">{{ $member->full_name }}</p>
                                    <p class="flex items-center gap-1 text-xs text-novix-muted">
                                        @if($reciprocal)
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><path d="M17 20h4v-2a4 4 0 0 0-3-3.87M13 3.13a4 4 0 0 1 0 7.75M3 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            Shared their profile with you
                                        @else
                                            <x-relation-icon :relation="$member->relation" class="h-3.5 w-3.5" />
                                            {{ Str::headline($member->relation) }}
                                        @endif
                                    </p>
                                </div>
                                <span class="flex-shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            </div>

                            <p class="mt-3 font-mono text-[11px] text-novix-muted">{{ $member->unique_health_id }}</p>

                            <div class="mt-4 flex flex-wrap gap-3 border-t border-gray-100 pt-3 text-xs font-semibold dark:border-white/10">
                                <a href="{{ route('family.show', $member) }}" class="text-novix-green hover:underline">View profile</a>

                                @if($member->access_type === 'dependent')
                                    <a href="{{ route('family.member.edit', $member) }}" class="text-novix-green hover:underline">Edit</a>
                                    <button type="button"
                                        x-data
                                        @click="$dispatch('open-archive-modal', { id: {{ $member->id }}, name: @js($member->full_name), url: @js(route('family.archive', $member)) })"
                                        class="text-novix-pink-dark hover:underline">
                                        Archive
                                    </button>
                                @else
                                    @if($member->linked_user_id !== auth()->id())
                                        @php($myGrant = $member->sharingPermissions->firstWhere('granted_to_user_id', auth()->id()))
                                        <span class="text-novix-muted">
                                            {{ $myGrant ? 'Sharing: '.Str::headline($myGrant->scope) : 'No access granted yet' }}
                                        </span>
                                        @if($myGrant?->scope === 'full')
                                            <a href="{{ route('family.member.edit', $member) }}" class="text-novix-green hover:underline">Edit</a>
                                        @endif
                                        {{-- Only the account that actually invited/owns this row can remove it —
                                             a reciprocal grant (an existing account that shared with you) only
                                             ever confers viewing rights, never management. --}}
                                        @if($member->primary_account_id === auth()->id())
                                            <button type="button"
                                                x-data
                                                @click="$dispatch('open-archive-modal', { id: {{ $member->id }}, name: @js($member->full_name), url: @js(route('family.archive', $member)), linked: true })"
                                                class="text-novix-pink-dark hover:underline">
                                                Remove
                                            </button>
                                        @endif
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Invited --}}
        @if($invited->isNotEmpty())
            <section>
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-novix-ink dark:text-white">
                    <span class="h-2 w-2 rounded-full bg-novix-yellow"></span>
                    Invited ({{ $invited->count() }})
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($invited as $member)
                        @include('family.partials.pending-card', ['member' => $member, 'invitation' => $member->invitations->first(), 'isExpired' => false])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Expired --}}
        @if($expired->isNotEmpty())
            <section>
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-novix-ink dark:text-white">
                    <span class="h-2 w-2 rounded-full bg-novix-pink-dark"></span>
                    Expired ({{ $expired->count() }})
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($expired as $member)
                        @include('family.partials.pending-card', ['member' => $member, 'invitation' => $member->invitations->first(), 'isExpired' => true])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- People I've shared my own profile with — the other half of every reciprocal
             grant. Without this, granting access is a one-way street: visible to the
             person who received it, invisible (and un-revokable) to the person who gave it. --}}
        @if($sharedWith->isNotEmpty())
            <section>
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-novix-ink dark:text-white">
                    <svg class="h-4 w-4 text-novix-green" viewBox="0 0 24 24" fill="none"><path d="M6 10V8a6 6 0 1 1 12 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    Your profile is shared with ({{ $sharedWith->count() }})
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($sharedWith as $grant)
                        <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                            <div class="flex items-center gap-3">
                                <x-avatar :photo-path="$grant->grantedToUser?->avatar_path" :full-name="$grant->grantedToUser->name ?? '?'" size="h-12 w-12" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-novix-ink dark:text-white">{{ $grant->grantedToUser->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-novix-muted">{{ $grant->grantedToUser->email ?? '' }}</p>
                                </div>
                            </div>

                            <span class="mt-3 inline-block rounded-full bg-novix-mint px-2.5 py-1 text-[11px] font-bold text-novix-green dark:bg-novix-green/20 dark:text-novix-mint">
                                {{ Str::headline($grant->scope) }} access{{ $grant->scope === 'full' ? ' · can edit' : '' }}
                            </span>

                            <div class="mt-4 border-t border-gray-100 pt-3 dark:border-white/10">
                                <form method="POST" action="{{ route('family.sharing.revoke', $grant) }}" onsubmit="return confirm('Revoke this access? They will no longer be able to view your profile.');">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-novix-pink-dark hover:underline">Revoke access</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Archive confirmation modal --}}
    <div
        x-data="{ open: false, name: '', url: '', linked: false }"
        @open-archive-modal.window="open = true; name = $event.detail.name; url = $event.detail.url; linked = $event.detail.linked ?? false"
        x-show="open" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        @keydown.escape.window="open = false"
    >
        <div @click.outside="open = false" x-show="open" x-transition class="w-full max-w-md rounded-novix bg-white p-6 shadow-novix dark:bg-novix-ink">
            <h3 class="text-lg font-bold text-novix-ink dark:text-white" x-text="linked ? `Remove ${name} from your family group?` : `Archive ${name}'s profile?`"></h3>
            <p class="mt-2 text-sm text-novix-muted">
                <span x-show="!linked">This archives their profile — their medical history is kept, not deleted, and you can restore this profile anytime from Account Security in your Profile settings.</span>
                <span x-show="linked">They'll no longer appear in your family list. If they have their own login, it stays theirs — only this connection is removed, and you can re-invite them anytime.</span>
            </p>
            <form method="POST" :action="url" class="mt-5 flex justify-end gap-3">
                @csrf
                <button type="button" @click="open = false" class="rounded-xl px-4 py-2 text-sm font-semibold text-novix-muted hover:text-novix-ink">Cancel</button>
                <button type="submit" class="rounded-xl bg-novix-pink-dark px-5 py-2 text-sm font-semibold text-white hover:bg-novix-pink-dark/90">Confirm</button>
            </form>
        </div>
    </div>
</x-app-layout>
