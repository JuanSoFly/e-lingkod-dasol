<?php

namespace App\Console\Commands;

use App\Services\LeaveAccrualService;
use Illuminate\Console\Command;

class LeaveAccrualCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:accrue-monthly {--year=} {--month=} {--backfill}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Accrue monthly leave credits for all eligible employees';

    /**
     * Execute the console command.
     */
    public function handle(LeaveAccrualService $service): int
    {
        $year = $this->option('year') ? (int) $this->option('year') : null;
        $month = $this->option('month') ? (int) $this->option('month') : null;
        $backfill = (bool) $this->option('backfill');

        if ($backfill) {
            $this->info("Starting backfill of monthly leave credits...");
            $created = $service->backfill($year);
        } else {
            $created = $service->accrueForMonth($year, $month);
        }

        $this->info("Leave accrual completed. Entries created: {$created}");

        return self::SUCCESS;
    }
}
