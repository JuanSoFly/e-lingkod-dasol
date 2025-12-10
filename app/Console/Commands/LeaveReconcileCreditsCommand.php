<?php

namespace App\Console\Commands;

use App\Services\LeaveAccrualService;
use Illuminate\Console\Command;

class LeaveReconcileCreditsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:reconcile-credits {year?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recompute leave credits for a year based on accrual logs';

    /**
     * Execute the console command.
     */
    public function handle(LeaveAccrualService $service): int
    {
        $year = $this->argument('year') ? (int) $this->argument('year') : now()->year;
        $updated = $service->reconcileYear($year);

        $this->info("Leave credits reconciled for {$year}. Records updated: {$updated}");

        return self::SUCCESS;
    }
}
