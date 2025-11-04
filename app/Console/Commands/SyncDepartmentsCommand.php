<?php

namespace App\Console\Commands;

use App\Services\DepartmentSyncService;
use Illuminate\Console\Command;

class SyncDepartmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'departments:sync
                            {--dry-run : Show what would be synchronized without making changes}
                            {--employee-id= : Sync specific employee by ID}
                            {--summary : Show synchronization summary before and after}
                            {--force : Force synchronization without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize employee department fields with their assigned offices';

    /**
     * Execute the console command.
     */
    public function handle(DepartmentSyncService $syncService): int
    {
        $dryRun = $this->option('dry-run');
        $employeeId = $this->option('employee-id');
        $showSummary = $this->option('summary');
        $force = $this->option('force');

        $this->info('🔄 Department Field Synchronization');
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
    private function syncSpecificEmployee(DepartmentSyncService $syncService, int $employeeId, bool $dryRun, bool $force): int
    {
        $employee = \App\Models\Employee::find($employeeId);

        if (!$employee) {
            $this->error("❌ Employee with ID {$employeeId} not found.");
            return 1;
        }

        $this->info("📋 Syncing employee: {$employee->full_name} (ID: {$employeeId})");
        $this->info("Current department: {$employee->department}");

        if ($employee->office) {
            $this->info("Assigned office: {$employee->office->name}");
        } else {
            $this->warn("⚠️  Employee has no assigned office");
            return 1;
        }

        if ($dryRun) {
            $validation = $syncService->validateEmployeeConsistency($employee);
            if ($validation['is_consistent']) {
                $this->info("✅ Employee department is already consistent");
            } else {
                $this->warn("⚠️  Department inconsistency found:");
                foreach ($validation['issues'] as $issue) {
                    $this->line("   - {$issue}");
                }
            }
            return 0;
        }

        if (!$force && !$this->confirm('Do you want to synchronize this employee\'s department?')) {
            $this->info('❌ Synchronization cancelled.');
            return 0;
        }

        $wasSynced = $syncService->synchronizeEmployeeDepartment($employee);

        if ($wasSynced) {
            $this->info("✅ Employee department synchronized successfully");
        } else {
            $this->info("ℹ️  No synchronization needed - department already matches office");
        }

        return 0;
    }

    /**
     * Synchronize all employees
     */
    private function syncAllEmployees(DepartmentSyncService $syncService, bool $dryRun, bool $force, bool $showSummary): int
    {
        // First show what inconsistencies exist
        $inconsistentEmployees = $syncService->findInconsistentEmployees();
        $count = count($inconsistentEmployees);

        if ($count === 0) {
            $this->info('✅ All employee departments are already synchronized!');
            return 0;
        }

        $this->info("📊 Found {$count} employees with inconsistent department assignments:");

        // Show table of inconsistent employees
        $this->table(
            ['ID', 'Name', 'Current Department', 'Expected Department'],
            collect($inconsistentEmployees)->map(function ($item) {
                return [
                    $item['employee']->id,
                    $item['employee']->full_name,
                    $item['current_department'],
                    $item['expected_department']
                ];
            })
        );

        if ($dryRun) {
            $this->info('🔍 DRY RUN - No changes will be made');
            return 0;
        }

        if (!$force && !$this->confirm("Do you want to synchronize {$count} employees?")) {
            $this->info('❌ Synchronization cancelled.');
            return 0;
        }

        $this->info('🔄 Synchronizing employee departments...');
        $results = $syncService->synchronizeAllDepartments();

        $this->displayResults($results);

        // Show final summary if requested
        if ($showSummary) {
            $this->newLine();
            $this->showSummary($syncService, 'After Synchronization');
        }

        return 0;
    }

    /**
     * Display synchronization results
     */
    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->info("📈 Results:");
        $this->line("✅ Processed: {$results['processed']} employees");
        $this->line("🔄 Updated: {$results['updated']} departments");
        $this->line("⏭️  Skipped: {$results['skipped']} (already consistent)");

        if (count($results['errors']) > 0) {
            $this->newLine();
            $this->error("❌ Errors:");
            foreach ($results['errors'] as $error) {
                $this->line("   - Employee {$error['employee_id']} ({$error['employee_name']}): {$error['error']}");
            }
        }
    }

    /**
     * Display synchronization summary
     */
    private function showSummary(DepartmentSyncService $syncService, string $title): void
    {
        $this->newLine();
        $this->info("📊 {$title}:");
        $this->info('--------------------------------');

        $summary = $syncService->getSyncSummary();

        $this->line("Total Employees: {$summary['total_employees']}");
        $this->line("Consistent: {$summary['consistent_employees']} ({$summary['consistency_rate']}%)");
        $this->line("Inconsistent: {$summary['inconsistent_employees']}");

        if ($summary['inconsistent_employees'] > 0) {
            $this->newLine();
            $this->warn('Employees with inconsistent departments:');
            $inconsistent = $syncService->findInconsistentEmployees();
            $this->table(
                ['ID', 'Name', 'Current Department', 'Expected Department'],
                collect($inconsistent)->map(function ($item) {
                    return [
                        $item['employee']->id,
                        $item['employee']->full_name,
                        $item['current_department'],
                        $item['expected_department']
                    ];
                })
            );
        }
    }
}