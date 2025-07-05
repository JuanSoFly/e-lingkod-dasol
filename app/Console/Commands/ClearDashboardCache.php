<?php

namespace App\Console\Commands;

use App\Contracts\DashboardServiceInterface;
use Illuminate\Console\Command;

class ClearDashboardCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:cache {action=clear : Action to perform (clear|warm)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage dashboard cache (clear or warm up)';

    /**
     * Execute the console command.
     */
    public function handle(DashboardServiceInterface $dashboardService)
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'clear':
                $this->info('Clearing dashboard cache...');
                $dashboardService->clearCache();
                $this->info('Dashboard cache cleared successfully!');
                break;

            case 'warm':
                $this->info('Warming up dashboard cache...');
                $dashboardService->warmCache();
                $this->info('Dashboard cache warmed up successfully!');
                break;

            default:
                $this->error("Invalid action: {$action}. Use 'clear' or 'warm'.");
                return 1;
        }

        return 0;
    }
}
