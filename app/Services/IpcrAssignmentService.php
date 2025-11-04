<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Office;
use App\Models\OfficeAssignment;
use App\Models\OPCRWorkflow;
use Illuminate\Support\Collection as SupportCollection;
use function collect;

class IpcrAssignmentService
{
    /**
     * Determine the employees who should receive cascaded IPCR items from an OPCR workflow.
     *
     * @param  OPCRWorkflow  $workflow
     * @param  array  $options
     * @return SupportCollection<int, array{employee: Employee, supervisor: ?Employee}>
     */
    public function determineAssignees(OPCRWorkflow $workflow, array $options = []): \Illuminate\Support\Collection
    {
        $office = $workflow->office ?: Office::find($options['office_id'] ?? null);

        if (!$office) {
            return collect();
        }

        $query = Employee::query()
            ->where('employment_status', '!=', 'Probationary')
            ->whereNull('archived_at')
            ->where(function ($q) use ($office, $options) {
                if (!empty($options['employee_ids'])) {
                    $q->whereIn('id', $options['employee_ids']);
                    return;
                }

                // Prefer explicit office_id linkage, fall back to department matching helper.
                $q->where('office_id', $office->id)
                  ->orWhere('department', $office->name);
            })
            ->orderBy('last_name');

        if (!empty($options['exclude_employee_ids'])) {
            $query->whereNotIn('id', (array) $options['exclude_employee_ids']);
        }

        $employees = $query->with(['user', 'officeAssignments' => function ($assignmentQuery) use ($office) {
            $assignmentQuery->current()->forOffice($office->id);
        }])->get();

        return $employees->map(function (Employee $employee) use ($office) {
            $supervisorAssignment = $employee->officeAssignments
                ->first(fn ($assignment) => $assignment->role === OfficeAssignment::ROLE_SUPERVISOR)
                ?: OfficeAssignment::query()
                    ->current()
                    ->forOffice($office->id)
                    ->byRole(OfficeAssignment::ROLE_SUPERVISOR)
                    ->first();

            return [
                'employee' => $employee,
                'supervisor' => optional($supervisorAssignment)->employee,
            ];
        })->filter(function (array $payload) {
            /** @var Employee $employee */
            $employee = $payload['employee'];

            if (!$employee->user) {
                return false;
            }

            if ($employee->employment_status === 'Resigned' || $employee->employment_status === 'Separated') {
                return false;
            }

            return true;
        });
    }
}
