<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Grants the admin-reporting-dashboard flag to whichever account is named
 * in the ADMIN_EMAIL env var. Runs automatically after migrations on every
 * boot (see docker/entrypoint.sh) so promoting an account is just "set the
 * env var, redeploy" — an auditable, version-controlled step rather than a
 * one-off manual database write. A no-op if ADMIN_EMAIL isn't set, if no
 * matching user exists yet, or if the flag is already set.
 */
class SyncAdminUser extends Command
{
    protected $signature = 'admin:sync';

    protected $description = 'Grant the admin dashboard flag to the account named in ADMIN_EMAIL, if not already set.';

    public function handle(): int
    {
        $email = config('admin.email');

        if (! $email) {
            return self::SUCCESS;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->warn("ADMIN_EMAIL is set to {$email}, but no user with that email exists yet.");

            return self::SUCCESS;
        }

        if ($user->is_admin) {
            return self::SUCCESS;
        }

        $user->forceFill(['is_admin' => true])->save();
        $this->info("Granted admin access to {$email}.");

        return self::SUCCESS;
    }
}
