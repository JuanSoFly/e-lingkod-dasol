<?php

namespace App\Console\Commands;

use App\Services\OfficeAssignmentSynchronizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncOfficeAssignmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'office:sync-assignments
                            {--dry-run : Show what would be synchronized without making changes}
                            {--employee-id= : Sync specific employee by ID}
                            {--force : Force synchronization without confirmation}
                            {--summary : Show consistency summary before and after}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize office assignments with employee office data';

    /**
     * Execute the console command.
     */
    public function handle(OfficeAssignmentSynchronizationService $syncService): int
    {
        $dryRun = $this->option('dry-run');
        $employeeId = $this->option('employee-id');
        $force = $this->option('force');
        $showSummary = $this->option('summary');

        $this->info('🔄 Office Assignment Synchronization');
        $this->info('================================');

        // Show initial summary if requested
        if ($showSummary) {
            $this->showSummary($syncService, 'Before Synchronization');
        }

        if ($employeeId) {
            return $this->syncSpecificEmployee($syncService, $employeeId, $dryRun, $force);
        } else {
            return $this->syncAllEmployees($syncService, $dryRun, $force, $showSummary);
        }
    }

    /**
     * Synchronize a specific employee
     */
    private function syncSpecificEmployee(OfficeAssignmentSynchronizationService $syncService, int $employeeId, bool $dryRun, bool $force): int
    {
        $employee = \App\Models\Employee::find($employeeId);

        if (!$employee) {
            $this->error("❌ Employee with ID {$employeeId} not found.");
            return 1;
        }

        $this->info("📋 Syncing employee: {$employee->full_name} (ID: {$employeeId})");
        $this->info("Current office: " . ($employee->office ? $employee->office->name : 'None'));

        if (!$dryRun && !$force) {
            if (!$this->confirm('Do you want to proceed with synchronization?')) {
                $this->info('❌ Synchronization cancelled.');
                return 0;
            }
        }

        $result = $syncService->synchronizeEmployee($employee);

        $this->displayResults([$result], $dryRun);

        return 0;
    }

    /**
     * Synchronize all employees
     */
    private function syncAllEmployees(OfficeAssignmentSynchronizationService $syncService, bool $dryRun, bool $force, bool $showSummary): int
    {
        // First show what inconsistencies exist
        $inconsistentEmployees = $syncService->findInconsistentAssignments();
        $count = count($inconsistentEmployees);

        if ($count === 0) {
            $this->info('✅ All office assignments are already consistent!');
            return 0;
        }

        $this->info("📊 Found {$count} employees with inconsistent office assignments:");

        // Show table of inconsistent employees
        $this->table(
            ['ID', 'Name', 'Current Office', 'Active Assignments', 'Issues'],
            collect($inconsistentEmployees)->map(function ($item) {
                $assignments = collect($item['active_assignments'])
                    ->where('is_active', true)
                    ->pluck('office')
                    ->implode(', ');

                return [
                    $item['employee']->id,
                    $item['employee']->full_name,
                    $item['employee_office'] ?? 'None',
                    $assignments ?: 'None',
                    implode('; ', array_slice($item['issues'], 0, 2))
                ];
            })
        );

        if (!$dryRun && !$force) {
            if (!$this->confirm("Do you want to synchronize {$count} employees?")) {
                $this->info('❌ Synchronization cancelled.');
                return 0;
            }
        }

        $this->info($dryRun ? '🔍 DRY RUN - No changes will be made:' : '🔄 Synchronizing employees...');

        $results = $syncService->synchronizeAll(['dry_run' => $dryRun]);
        $this->displayResults([$results], $dryRun);

        // Show final summary if requested
        if ($showSummary && !$dryRun) {
            $this->newLine();
            $this->showSummary($syncService, 'After Synchronization');
        }

        return 0;
    }

    /**
     * Display synchronization results
     */
    private function displayResults(array $results, bool $dryRun): void
    {
        $result = $results[0];

        // Check if this is a single employee sync (no 'processed' key)
        if (!isset($result['processed'])) {
            if ($dryRun) {
                $this->info("🔍 DRY RUN - No changes would be made.");
            } else {
                $this->newLine();
                $this->info("📈 Results:");
                $this->line("🔄 Updated: " . count($result['updated'] ?? []) . " assignments");
                $this->line("➕ Created: " . count($result['created'] ?? []) . " assignments");
                $this->line("➖ Deactivated: " . count($result['deactivated'] ?? []) . " assignments");

                if (count($result['errors'] ?? []) > 0) {
                    $this->newLine();
                    $this->error("❌ Errors:");
                    foreach ($result['errors'] as $error) {
                        $this->line("   - {$error}");
                    }
                }
            }
            return;
        }

        if ($dryRun && isset($result['dry_run_results'])) {
            $this->table(
                ['Employee ID', 'Name', 'Would Sync To', 'Issues'],
                collect($result['dry_run_results'])->map(function ($item) {
                    return [
                        $item['employee_id'],
                        $item['employee'],
                        $item['would_sync_to'],
                        implode('; ', array_slice($item['issues'], 0, 2))
                    ];
                })
            );
        } elseif (!$dryRun) {
            $this->newLine();
            $this->info("📈 Results:");
            $this->line("✅ Processed: {$result['processed']} employees");
            $this->line("🔄 Updated: " . count($result['updated']) . " assignments");
            $this->line("➕ Created: " . count($result['created']) . " assignments");
            $this->line("➖ Deactivated: " . count($result['deactivated']) . " assignments");

            if (count($result['errors']) > 0) {
                $this->newLine();
                $this->error("❌ Errors:");
                foreach ($result['errors'] as $error) {
                    $this->line("   - {$error}");
                }
            }
        }
    }

    /**
     * Display consistency summary
     */
    private function showSummary(OfficeAssignmentSynchronizationService $syncService, string $title): void
    {
        $this->newLine();
        $this->info("📊 {$title}:");
        $this->info('--------------------------------');

        $summary = $syncService->getConsistencySummary();

        $this->line("Total Employees: {$summary['total_employees']}");
        $this->line("Consistent: {$summary['consistent_employees']} ({$summary['consistency_rate']}%)");
        $this->line("Inconsistent: {$summary['inconsistent_employees']}");

        if ($summary['problematic_offices']->count() > 0) {
            $this->newLine();
            $this->warn('Offices with inconsistencies:');
            $this->table(
                ['Office', 'Employee Count', 'Assignment Count', 'Consistent'],
                $summary['problematic_offices']->map(function ($office) {
                    return [
                        $office['name'],
                        $office['employee_count'],
                        $office['assignment_count'],
                        $office['is_consistent'] ? '✅' : '❌'
                    ];
                })
            );
        }
    }
}