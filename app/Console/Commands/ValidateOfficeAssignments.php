<?php

namespace App\Console\Commands;

use App\Models\OfficeAssignment;
use App\Models\Employee;
use App\Services\EmployeeDepartmentSyncService;
use Illuminate\Console\Command;

class ValidateOfficeAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'office:validate-assignments
                            {--fix : Attempt to fix identified issues}
                            {--sync-departments : Sync employee departments with assignments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate office assignment data integrity and fix inconsistencies';

    /**
     * Execute the console command.
     */
    public function handle(EmployeeDepartmentSyncService $departmentSyncService)
    {
        $this->info('🔍 Validating Office Assignment Data Integrity...');
        $this->newLine();

        $issues = [];
        $fixes = [];

        // Check 1: Duplicate active assignments
        $this->info('Checking for duplicate active assignments...');
        $duplicates = $this->findDuplicateActiveAssignments();
        if ($duplicates->isNotEmpty()) {
            $issues['duplicate_assignments'] = $duplicates;
            $this->error("Found {$duplicates->count()} duplicate active assignment issues");

            if ($this->option('fix')) {
                $fixes['duplicate_assignments'] = $this->fixDuplicateAssignments($duplicates);
            }
        } else {
            $this->info('✅ No duplicate active assignments found');
        }

        // Check 2: Orphaned assignments (employee not found)
        $this->newLine();
        $this->info('Checking for orphaned assignments...');
        $orphaned = $this->findOrphanedAssignments();
        if ($orphaned->isNotEmpty()) {
            $issues['orphaned_assignments'] = $orphaned;
            $this->error("Found {$orphaned->count()} orphaned assignments");

            if ($this->option('fix')) {
                $fixes['orphaned_assignments'] = $this->fixOrphanedAssignments($orphaned);
            }
        } else {
            $this->info('✅ No orphaned assignments found');
        }

        // Check 3: Invalid date ranges
        $this->newLine();
        $this->info('Checking for invalid date ranges...');
        $invalidDates = $this->findInvalidDateRanges();
        if ($invalidDates->isNotEmpty()) {
            $issues['invalid_dates'] = $invalidDates;
            $this->error("Found {$invalidDates->count()} assignments with invalid date ranges");

            if ($this->option('fix')) {
                $fixes['invalid_dates'] = $this->fixInvalidDateRanges($invalidDates);
            }
        } else {
            $this->info('✅ No invalid date ranges found');
        }

        // Check 4: Department inconsistencies
        $this->newLine();
        $this->info('Checking for department inconsistencies...');
        $inconsistencies = $departmentSyncService->validateDepartmentConsistency();
        if (!empty($inconsistencies)) {
            $issues['department_inconsistencies'] = $inconsistencies;
            $this->error("Found " . count($inconsistencies) . " department inconsistencies");

            if ($this->option('fix') || $this->option('sync-departments')) {
                $fixResults = $departmentSyncService->fixDepartmentInconsistencies();
                $fixes['department_inconsistencies'] = $fixResults;
            }
        } else {
            $this->info('✅ No department inconsistencies found');
        }

        // Summary Report
        $this->newLine();
        $this->info('📊 Validation Summary:');

        $duplicateCount = isset($issues['duplicate_assignments']) ? $issues['duplicate_assignments']->count() : 0;
        $orphanedCount = isset($issues['orphaned_assignments']) ? $issues['orphaned_assignments']->count() : 0;
        $invalidDatesCount = isset($issues['invalid_dates']) ? $issues['invalid_dates']->count() : 0;
        $departmentInconsistenciesCount = count($issues['department_inconsistencies'] ?? []);

        $this->table(['Issue Type', 'Count', 'Fixed'], [
            ['Duplicate Assignments', $duplicateCount, $fixes['duplicate_assignments'] ?? 0],
            ['Orphaned Assignments', $orphanedCount, $fixes['orphaned_assignments'] ?? 0],
            ['Invalid Date Ranges', $invalidDatesCount, $fixes['invalid_dates'] ?? 0],
            ['Department Inconsistencies', $departmentInconsistenciesCount, $fixes['department_inconsistencies']['fixed'] ?? 0],
        ]);

        $totalIssues = $duplicateCount + $orphanedCount + $invalidDatesCount + $departmentInconsistenciesCount;

        if ($totalIssues === 0) {
            $this->newLine();
            $this->info('🎉 All validations passed! Office assignment data is clean.');
        } else {
            $this->newLine();
            $this->warn("Found {$totalIssues} total issues. Run with --fix to resolve automatically.");
        }

        return $totalIssues === 0 ? 0 : 1;
    }

    /**
     * Find duplicate active assignments for same employee+office
     */
    private function findDuplicateActiveAssignments()
    {
        return OfficeAssignment::selectRaw('employee_id, office_id, COUNT(*) as duplicate_count')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->groupBy('employee_id', 'office_id')
            ->havingRaw('COUNT(*) > 1')
            ->with(['employee', 'office'])
            ->get();
    }

    /**
     * Fix duplicate active assignments
     */
    private function fixDuplicateAssignments($duplicates)
    {
        $fixed = 0;
        foreach ($duplicates as $duplicate) {
            // Get all active assignments for this employee+office, ordered by creation date
            $assignments = OfficeAssignment::where('employee_id', $duplicate->employee_id)
                ->where('office_id', $duplicate->office_id)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('ended_date')
                          ->orWhere('ended_date', '>=', now());
                })
                ->orderBy('created_at', 'desc')
                ->get();

            // Keep the most recent one active, deactivate others
            $keepActive = $assignments->first();
            $deactivate = $assignments->skip(1);

            foreach ($deactivate as $assignment) {
                $assignment->update([
                    'is_active' => false,
                    'ended_date' => now(),
                ]);
                $fixed++;
            }

            $this->info("Deactivated {$deactivate->count()} duplicate assignments for employee ID: {$duplicate->employee_id} in office ID: {$duplicate->office_id}");
        }

        return $fixed;
    }

    /**
     * Find orphaned assignments (employee not found)
     */
    private function findOrphanedAssignments()
    {
        return OfficeAssignment::whereDoesntHave('employee')
            ->with('office')
            ->get();
    }

    /**
     * Fix orphaned assignments
     */
    private function fixOrphanedAssignments($orphaned)
    {
        $fixed = 0;
        foreach ($orphaned as $assignment) {
            // Soft delete orphaned assignments
            $assignment->delete();
            $fixed++;
            $this->info("Deleted orphaned assignment ID: {$assignment->id}");
        }

        return $fixed;
    }

    /**
     * Find assignments with invalid date ranges
     */
    private function findInvalidDateRanges()
    {
        return OfficeAssignment::where(function ($query) {
            $query->whereNotNull('ended_date')
                  ->whereRaw('ended_date < assigned_date')
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('is_active', true)
                               ->whereNotNull('ended_date')
                               ->where('ended_date', '<', now());
                  });
        })->get();
    }

    /**
     * Fix invalid date ranges
     */
    private function fixInvalidDateRanges($invalidDates)
    {
        $fixed = 0;
        foreach ($invalidDates as $assignment) {
            if ($assignment->ended_date < $assignment->assigned_date) {
                // Fix invalid date range by ending the assignment today
                $assignment->update([
                    'ended_date' => now(),
                    'is_active' => false,
                ]);
                $fixed++;
                $this->info("Fixed invalid date range for assignment ID: {$assignment->id}");
            } elseif ($assignment->is_active && $assignment->ended_date && $assignment->ended_date < now()) {
                // Deactivate assignments that should be inactive
                $assignment->update(['is_active' => false]);
                $fixed++;
                $this->info("Deactivated expired assignment ID: {$assignment->id}");
            }
        }

        return $fixed;
    }
}
