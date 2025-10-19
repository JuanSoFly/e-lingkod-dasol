<?php

namespace App\Console\Commands;

use App\Services\LeaveWorkflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLeaveEscalations extends Command
{
    protected $signature = 'leave:check-escalations';
    protected $description = 'Check and process pending leave application escalations';

    public function __construct(private LeaveWorkflowService $workflowService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking for pending leave application escalations...');

        try {
            $escalatedCount = $this->workflowService->checkEscalations();

            if ($escalatedCount > 0) {
                $this->info("Successfully escalated {$escalatedCount} pending leave applications.");
                Log::info('Leave escalation check completed', [
                    'escalated_count' => $escalatedCount,
                ]);
            } else {
                $this->info('No pending applications required escalation.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Error checking leave escalations: ' . $e->getMessage());
            Log::error('Leave escalation check failed', [
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }
}
