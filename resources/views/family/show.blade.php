@php
    $activeGrant = $member->access_type === 'linked'
        ? $member->sharingPermissions()->where('granted_to_user_id', auth()->id())->whereNull('revoked_at')->first()
        : null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-novix-ink dark:text-white">{{ $member->full_name }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <a href="{{ route('family.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-novix-green hover:underline">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Back to family
        </a>

        <div class="overflow-hidden rounded-novix bg-novix-green shadow-novix">
            <div class="flex flex-col items-center gap-6 p-6 text-white sm:flex-row sm:p-8">
                <x-avatar :photo-path="$member->photo_path" :preset="$member->avatar_preset ?? null" :full-name="$member->full_name" :gender="$member->gender" :age="$member->age()"
                    size="h-20 w-20" color-class="bg-white/10 text-white" class="flex-shrink-0 border-4 border-white/25" />
                <div class="text-center sm:text-left">
                    <h1 class="text-xl font-bold">{{ $member->full_name }}</h1>
                    <p class="mt-1 flex items-center justify-center gap-1.5 text-sm text-white/70 sm:justify-start">
                        <x-relation-icon :relation="$member->relation" class="h-3.5 w-3.5" />
                        {{ Str::headline($member->relation) }} &middot; {{ $member->unique_health_id }}
                    </p>
                    <span class="mt-2 inline-block rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">
                        {{ $member->access_type === 'linked' ? 'Linked account' : 'Dependent profile' }}
                    </span>
                </div>
            </div>
        </div>

        @if(!$canEdit)
            <div class="mt-6 rounded-novix bg-white p-6 text-center shadow-novix-sm dark:bg-white/5">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-novix-cream text-novix-muted dark:bg-white/10">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M6 10V8a6 6 0 1 1 12 0v2M5 10h14v10H5V10Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                </span>
                @if($activeGrant)
                    <p class="mt-3 text-sm text-novix-ink dark:text-white">{{ $member->full_name }} has shared <strong>{{ Str::headline($activeGrant->scope) }}</strong> access with you — full profile details aren't visible from here.</p>
                @else
                    <p class="mt-3 text-sm text-novix-ink dark:text-white">{{ $member->full_name }} hasn't granted you access to their profile yet.</p>
                    <p class="mt-1 text-xs text-novix-muted">Only they can choose to share their data — this is their decision to make from their own account.</p>
                @endif
            </div>
        @else
            @if($member->access_type === 'linked')
                <div class="mt-6 flex items-center gap-2 rounded-novix bg-novix-mint/60 px-4 py-3 text-sm font-medium text-novix-green dark:bg-novix-green/15 dark:text-novix-mint">
                    <svg class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4.5 8-11V5l-8-3-8 3v6c0 6.5 8 11 8 11Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ $member->full_name }} has given you full access — you can view and edit their profile.
                </div>
            @endif

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-novix-muted">Basic info</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-novix-muted">Date of birth</dt><dd class="text-novix-ink dark:text-white">{{ $member->date_of_birth?->format('M j, Y') ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-novix-muted">Gender</dt><dd class="text-novix-ink dark:text-white capitalize">{{ $member->gender ? str_replace('_', ' ', $member->gender) : '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-novix-muted">Blood group</dt><dd class="text-novix-ink dark:text-white">{{ $member->blood_group ?? '—' }}</dd></div>
                    </dl>
                </div>
                <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-novix-muted">Emergency contact</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-novix-muted">Name</dt><dd class="text-novix-ink dark:text-white">{{ $member->emergency_contact_name ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-novix-muted">Phone</dt><dd class="text-novix-ink dark:text-white">{{ $member->emergency_contact_phone ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-novix-muted">Relation</dt><dd class="text-novix-ink dark:text-white capitalize">{{ $member->emergency_contact_relation ?? '—' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-novix-muted">BMI</h3>
                    @if($latestBmi)
                        <div class="mt-2">
                            <x-bmi-gauge :height-cm="$latestBmi->height_cm" :weight-kg="$latestBmi->weight_kg" :editable="false" :size="160" />
                        </div>
                        <p class="mt-1 text-center text-xs text-novix-muted">Last recorded {{ $latestBmi->recorded_date->diffForHumans() }}</p>
                    @else
                        <p class="mt-4 text-center text-sm text-novix-muted">No BMI recorded yet.</p>
                    @endif
                </div>

                <div class="space-y-4">
                    <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-novix-muted">Allergies</h3>
                        @if($allergies->isEmpty())
                            <p class="mt-2 text-sm text-novix-muted">None recorded.</p>
                        @else
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach($allergies as $allergen)
                                    <span class="rounded-full bg-novix-pink/25 px-2.5 py-1 text-xs font-semibold text-novix-pink-dark">{{ $allergen }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="rounded-novix bg-white p-5 shadow-novix-sm dark:bg-white/5">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-novix-muted">Current medications</h3>
                        @if($medications->isEmpty())
                            <p class="mt-2 text-sm text-novix-muted">None recorded.</p>
                        @else
                            <ul class="mt-2 space-y-1 text-sm text-novix-ink dark:text-white">
                                @foreach($medications as $medication)
                                    <li>{{ $medication->medicine_name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <a href="{{ route('family.member.edit', $member) }}" class="rounded-xl bg-novix-green px-5 py-2.5 text-sm font-semibold text-white shadow-novix-sm hover:bg-novix-green-dark">Edit profile</a>
            </div>
        @endif
    </div>
</x-app-layout>
