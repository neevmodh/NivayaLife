<div class="space-y-10">
    <div>
        <h3 class="text-lg font-bold text-novix-ink dark:text-white">Change password</h3>
        <p class="mt-1 text-sm text-novix-muted">
            @if($user->google_id && !$user->password)
                Your account currently signs in via Google only.
            @else
                Choose a new password for your account.
            @endif
        </p>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            @method('put')

            <x-floating-input type="password" name="current_password" label="Current password" :required="true" autocomplete="current-password" :error="$errors->updatePassword->first('current_password')" />
            <x-floating-input type="password" name="password" label="New password" :required="true" autocomplete="new-password" :error="$errors->updatePassword->first('password')" />
            <x-floating-input type="password" name="password_confirmation" label="Confirm new password" :required="true" autocomplete="new-password" />

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-xl bg-novix-green px-6 py-2.5 text-sm font-semibold text-white shadow-novix-sm hover:bg-novix-green-dark">
                    Update password
                </button>
                @if(session('status') === 'password-updated')
                    <span class="flex items-center gap-1.5 text-sm font-semibold text-novix-green">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Saved
                    </span>
                @endif
            </div>
        </form>
    </div>

    @if($archivedMembers->isNotEmpty())
        <div class="border-t border-gray-100 pt-8 dark:border-white/10">
            <h3 class="text-lg font-bold text-novix-ink dark:text-white">Archived family members</h3>
            <p class="mt-1 text-sm text-novix-muted">Their medical history was kept, not deleted. Restore anytime.</p>

            <div class="mt-4 space-y-2">
                @foreach($archivedMembers as $archived)
                    <div class="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3 dark:border-white/10">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-500 dark:bg-white/10">{{ strtoupper(substr($archived->full_name, 0, 1)) }}</span>
                            <div>
                                <p class="text-sm font-medium text-novix-ink dark:text-white">{{ $archived->full_name }}</p>
                                <p class="text-xs text-novix-muted">Archived {{ $archived->deleted_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('family.restore', $archived->id) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-novix-green/30 px-3 py-1.5 text-xs font-semibold text-novix-green hover:bg-novix-mint/40">Restore</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="border-t border-gray-100 pt-8 dark:border-white/10">
        <h3 class="text-lg font-bold text-novix-ink dark:text-white">Linked accounts</h3>
        <div class="mt-4 flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3 dark:border-white/10">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/><path fill="#FBBC05" d="M5.27 14.29c-.25-.72-.38-1.49-.38-2.29s.14-1.57.38-2.29V6.62H1.29A11.96 11.96 0 0 0 0 12c0 1.94.46 3.77 1.29 5.38l3.98-3.09z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.94 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75z"/></svg>
                <span class="text-sm font-medium text-novix-ink dark:text-white">Google</span>
            </div>
            @if($user->google_id)
                <span class="rounded-full bg-novix-mint px-3 py-1 text-xs font-semibold text-novix-green">Connected</span>
            @else
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500">Not connected</span>
            @endif
        </div>
    </div>

    <div class="border-t border-novix-pink-dark/20 pt-8" x-data="{ confirming: false, typed: '' }">
        <h3 class="text-lg font-bold text-novix-pink-dark">Danger zone</h3>
        <p class="mt-1 text-sm text-novix-muted">Deleting your account permanently removes your profile, reports, and all associated data. This cannot be undone.</p>

        <button type="button" x-show="!confirming" @click="confirming = true"
            class="mt-4 rounded-xl border border-novix-pink-dark px-6 py-2.5 text-sm font-semibold text-novix-pink-dark hover:bg-novix-pink-dark hover:text-white">
            Delete my account and all data
        </button>

        <form x-show="confirming" x-cloak method="POST" action="{{ route('profile.destroy') }}" class="mt-4 space-y-4 rounded-xl border border-novix-pink-dark/30 bg-novix-pink/5 p-5">
            @csrf
            @method('delete')

            <x-floating-input type="password" name="password" label="Your password" :required="true" :error="$errors->userDeletion->first('password') ?? null" />

            <div>
                <label class="mb-1.5 block text-xs font-semibold text-novix-muted">Type <strong>DELETE</strong> to confirm</label>
                <input type="text" name="confirmation" x-model="typed"
                    class="w-full rounded-xl border border-novix-pink-dark/40 bg-white px-4 py-3 text-sm focus:border-novix-pink-dark focus:outline-none focus:ring-2 focus:ring-novix-pink-dark/20">
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" :disabled="typed !== 'DELETE'"
                    class="rounded-xl bg-novix-pink-dark px-6 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">
                    Permanently delete account
                </button>
                <button type="button" @click="confirming = false" class="text-sm font-semibold text-novix-muted hover:text-novix-ink">Cancel</button>
            </div>
        </form>
    </div>
</div>
