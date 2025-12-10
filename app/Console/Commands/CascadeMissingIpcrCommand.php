<?php

namespace App\Console\Commands;

use App\Services\IpcrCascadeScheduler;
use Illuminate\Console\Command;

class CascadeMissingIpcrCommand extends Command
{
    protected $signature = 'ipcr:cascade-missing {--office=} {--workflow=} {--force}';

    protected $description = 'Queue cascades for approved OPCRs that have not generated IPCR records.';

    public function handle(IpcrCascadeScheduler $scheduler): int
    {
        $officeId = $this->option('office') ? (int) $this->option('office') : null;
        $workflowId = $this->option('workflow') ? (int) $this->option('workflow') : null;
        $force = (bool) $this->option('force');

        $result = $scheduler->reconcile($officeId, $workflowId, $force);

        $this->info("Queued cascades for {$result['count']} workflow(s).");

        if ($result['count'] > 0) {
            $this->line('Workflow IDs: ' . implode(', ', $result['processed']));
        }

        return self::SUCCESS;
    }
}

