<?php

namespace App\Console\Commands;

use App\Models\FamilyInvitation;
use Illuminate\Console\Command;

class ExpireFamilyInvitations extends Command
{
    protected $signature = 'app:expire-family-invitations';

    protected $description = 'Mark pending family invitations past their expires_at as expired';

    public function handle(): int
    {
        $count = FamilyInvitation::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Marked {$count} invitation(s) as expired.");

        return self::SUCCESS;
    }
}
