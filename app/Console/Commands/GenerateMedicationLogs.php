<?php

namespace App\Console\Commands;

use App\Models\Medication;
use Illuminate\Console\Command;

/**
 * Creates today's pending dose-log rows for every active, scheduled
 * medication — without this, the dashboard's dose pills and the reminder
 * pipeline have nothing to reference, since neither has ever generated
 * these rows itself.
 */
class GenerateMedicationLogs extends Command
{
    protected $signature = 'app:generate-medication-logs';

    protected $description = "Create today's pending dose-log rows for every active, scheduled medication";

    public function handle(): int
    {
        $today = today();
        $created = 0;

        Medication::where('active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->chunkById(100, function ($medications) use (&$created) {
                foreach ($medications as $medication) {
                    $created += $medication->generateTodaysLogs();
                }
            });

        $this->info("Created {$created} dose log(s) for today.");

        return self::SUCCESS;
    }
}
