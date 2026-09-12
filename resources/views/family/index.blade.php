@php
    $accessBadge = fn ($member) => $member->access_type === 'linked'
        ? ['label' => 'Linked', 'class' => 'bg-nivayalife-blue/20 text-nivayalife-blue']
        : ['label' => 'Dependent', 'class' => 'bg-nivayalife-mint text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint'];
    $isReciprocal = fn ($member) => $member->primary_account_id !== auth()->id() && $member->linked_user_id !== auth()->id();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold leading-tight tracking-tight text-nivayalife-ink dark:text-white">Family</h2>
                <p class="mt-1 text-sm text-nivayalife-muted">Everyone linked to your Nivaya Life account.</p>
            </div>
            <a href="{{ route('family.add') }}"
                class="flex items-center gap-2 rounded-xl bg-nivayalife-green px-5 py-2.5 text-sm font-semibold text-white shadow-nivayalife-sm transition hover:-translate-y-0.5 hover:bg-nivayalife-green-dark active:translate-y-0 active:scale-95">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                Add Family Member
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">

        @if(session('status'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                class="rounded-xl bg-nivayalife-mint px-4 py-3 text-sm font-semibold text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint">
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
            <div class="mb-4 flex items-center gap-3">
                <h3 class="flex items-center gap-2 text-[13px] font-bold uppercase tracking-wide text-nivayalife-muted">
                    <span class="h-2 w-2 rounded-full bg-nivayalife-green"></span>
                    Active
                </h3>
                <span class="rounded-full bg-nivayalife-mint px-2 py-0.5 text-[11px] font-bold text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint">{{ $active->count() }}</span>
                <span class="h-px flex-1 bg-gray-100 dark:bg-white/10" aria-hidden="true"></span>
            </div>

            @if($active->isEmpty())
                <div class="rounded-nivayalife bg-white shadow-nivayalife-sm dark:bg-white/5">
                    <x-empty-state
                        title="No family members yet"
                        hint="Add a parent, partner or child and their records live alongside yours."
                        action-label="Add a family member"
                        :action-url="route('family.add')"
                        class="py-12" />
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($active as $member)
                        @php($badge = $accessBadge($member))
                        @php($reciprocal = $isReciprocal($member))
                        <div class="nivayalife-gold-edge group rounded-nivayalife bg-white p-5 shadow-nivayalife-sm transition hover:-translate-y-0.5 hover:shadow-nivayalife dark:bg-white/5 {{ $reciprocal ? 'border border-nivayalife-blue/20' : '' }}">
                            <div class="flex items-start gap-3.5">
                                <x-avatar :photo-path="$member->photo_path" :preset="$member->avatar_preset ?? null" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()" size="h-16 w-16" class="flex-shrink-0 shadow-sm transition group-hover:scale-105" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-nivayalife-ink dark:text-white">{{ $member->full_name }}</p>
                                    <p class="flex items-center gap-1 text-xs text-nivayalife-muted">
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

                            <p class="mt-3 font-mono text-[11px] text-nivayalife-muted">{{ $member->unique_health_id }}</p>

                            <div class="mt-3 grid grid-cols-3 divide-x divide-gray-100 border-t border-gray-100 pt-3 text-center dark:divide-white/10 dark:border-white/10">
                                <div>
                                    <p class="text-sm font-extrabold text-nivayalife-ink dark:text-white">{{ $member->reports_count }}</p>
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-nivayalife-muted">Reports</p>
                                </div>
                                <div>
                                    <p class="text-sm font-extrabold text-nivayalife-ink dark:text-white">{{ $member->medications_count }}</p>
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-nivayalife-muted">Meds</p>
                                </div>
                                <div>
                                    <p class="text-sm font-extrabold text-nivayalife-ink dark:text-white">{{ $member->vaccinations_count }}</p>
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-nivayalife-muted">Vaccines</p>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-3 border-t border-gray-100 pt-3 text-xs font-semibold dark:border-white/10">
                                <a href="{{ route('family.show', $member) }}" class="text-nivayalife-green hover:underline">View profile</a>

                                @if($member->access_type === 'dependent')
                                    <a href="{{ route('family.member.edit', $member) }}" class="text-nivayalife-green hover:underline">Edit</a>
                                    <button type="button"
                                        x-data
                                        @click="$dispatch('open-archive-modal', { id: {{ $member->id }}, name: @js($member->full_name), url: @js(route('family.archive', $member)) })"
                                        class="text-nivayalife-pink-dark hover:underline">
                                        Archive
                                    </button>
                                @else
                                    @if($member->linked_user_id !== auth()->id())
                                        @php($myGrant = $member->sharingPermissions->firstWhere('granted_to_user_id', auth()->id()))
                                        <span class="text-nivayalife-muted">
                                            {{ $myGrant ? 'Sharing: '.Str::headline($myGrant->scope) : 'No access granted yet' }}
                                        </span>
                                        @if($myGrant?->scope === 'full')
                                            <a href="{{ route('family.member.edit', $member) }}" class="text-nivayalife-green hover:underline">Edit</a>
                                        @endif
                                        {{-- Only the account that actually invited/owns this row can remove it —
                                             a reciprocal grant (an existing account that shared with you) only
                                             ever confers viewing rights, never management. --}}
                                        @if($member->primary_account_id === auth()->id())
                                            <button type="button"
                                                x-data
                                                @click="$dispatch('open-archive-modal', { id: {{ $member->id }}, name: @js($member->full_name), url: @js(route('family.archive', $member)), linked: true })"
                                                class="text-nivayalife-pink-dark hover:underline">
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
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-nivayalife-ink dark:text-white">
                    <span class="h-2 w-2 rounded-full bg-nivayalife-yellow"></span>
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
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-nivayalife-ink dark:text-white">
                    <span class="h-2 w-2 rounded-full bg-nivayalife-pink-dark"></span>
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
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-nivayalife-ink dark:text-white">
                    <svg class="h-4 w-4 text-nivayalife-green" viewBox="0 0 24 24" fill="none"><path d="M6 10V8a6 6 0 1 1 12 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    Your profile is shared with ({{ $sharedWith->count() }})
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($sharedWith as $grant)
                        <div class="rounded-nivayalife bg-white p-5 shadow-nivayalife-sm dark:bg-white/5">
                            <div class="flex items-center gap-3">
                                <x-avatar :photo-path="$grant->grantedToUser?->avatar_path" :full-name="$grant->grantedToUser->name ?? '?'" size="h-12 w-12" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-nivayalife-ink dark:text-white">{{ $grant->grantedToUser->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-nivayalife-muted">{{ $grant->grantedToUser->email ?? '' }}</p>
                                </div>
                            </div>

                            <span class="mt-3 inline-block rounded-full bg-nivayalife-mint px-2.5 py-1 text-[11px] font-bold text-nivayalife-green dark:bg-nivayalife-green/20 dark:text-nivayalife-mint">
                                {{ Str::headline($grant->scope) }} access{{ $grant->scope === 'full' ? ' · can edit' : '' }}
                            </span>

                            <div class="mt-4 border-t border-gray-100 pt-3 dark:border-white/10">
                                <form method="POST" action="{{ route('family.sharing.revoke', $grant) }}" onsubmit="return confirm('Revoke this access? They will no longer be able to view your profile.');">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-nivayalife-pink-dark hover:underline">Revoke access</button>
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
        <div @click.outside="open = false" x-show="open" x-transition class="w-full max-w-md rounded-nivayalife bg-white p-6 shadow-nivayalife dark:bg-nivayalife-night">
            <h3 class="text-lg font-bold text-nivayalife-ink dark:text-white" x-text="linked ? `Remove ${name} from your family group?` : `Archive ${name}'s profile?`"></h3>
            <p class="mt-2 text-sm text-nivayalife-muted">
                <span x-show="!linked">This archives their profile — their medical history is kept, not deleted, and you can restore this profile anytime from Account Security in your Profile settings.</span>
                <span x-show="linked">They'll no longer appear in your family list. If they have their own login, it stays theirs — only this connection is removed, and you can re-invite them anytime.</span>
            </p>
            <form method="POST" :action="url" class="mt-5 flex justify-end gap-3">
                @csrf
                <button type="button" @click="open = false" class="rounded-xl px-4 py-2 text-sm font-semibold text-nivayalife-muted hover:text-nivayalife-ink">Cancel</button>
                <button type="submit" class="rounded-xl bg-nivayalife-pink-dark px-5 py-2 text-sm font-semibold text-white hover:bg-nivayalife-pink-dark/90">Confirm</button>
            </form>
        </div>
    </div>
</x-app-layout>
