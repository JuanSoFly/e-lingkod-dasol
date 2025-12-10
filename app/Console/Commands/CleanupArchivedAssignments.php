<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\OfficeAssignmentService;
use Illuminate\Console\Command;

class CleanupArchivedAssignments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'office:cleanup-archived-assignments {--employee-id=} {--dry-run}';

    /**
     * The console command description.
     */
    protected $description = 'Deactivate office assignments that still point to archived employees or deleted users.';

    public function handle(OfficeAssignmentService $assignmentService): int
    {
        $employeeId = $this->option('employee-id');
        $dryRun = (bool) $this->option('dry-run');

        $query = Employee::withTrashed()
            ->where(function ($builder) {
                $builder->whereNotNull('archived_at')
                    ->orWhereNotNull('deleted_at');
            });

        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        $employees = $query->get();

        if ($employees->isEmpty()) {
            $this->info('No archived employees matched the provided filters.');
            return 0;
        }

        $totalImpacted = 0;

        foreach ($employees as $employee) {
            $label = trim(($employee->full_name ?? '') ?: $employee->first_name . ' ' . $employee->last_name);

            if ($dryRun) {
                $count = $employee->officeAssignments()
                    ->where('is_active', true)
                    ->count();

                $this->line("[DRY RUN] {$label} (ID {$employee->id}) would deactivate {$count} assignment(s).");
                continue;
            }

            $result = $assignmentService->deactivateAssignmentsForEmployee($employee, 'cleanup command');

            if ($result['count'] > 0) {
                $this->info("Deactivated {$result['count']} assignment(s) for {$label} (ID {$employee->id}).");
                $totalImpacted += $result['count'];
            }
        }

        if ($dryRun) {
            $this->info('Dry run finished. No database changes were made.');
        } else {
            $this->info("Cleanup finished. Total assignments deactivated: {$totalImpacted}.");
        }

        return 0;
    }
}
