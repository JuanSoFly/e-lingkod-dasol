<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Office;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepartmentSyncService
{
    /**
     * Synchronize department field for a specific employee to match their assigned office
     */
    public function synchronizeEmployeeDepartment(Employee $employee): bool
    {
        try {
            $office = $employee->office;
            if (!$office) {
                Log::warning('Cannot synchronize department: Employee has no assigned office', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name
                ]);
                return false;
            }

            $oldDepartment = $employee->department;
            $newDepartment = $office->name;

            if ($oldDepartment !== $newDepartment) {
                $employee->update(['department' => $newDepartment]);

                Log::info('Employee department synchronized', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'old_department' => $oldDepartment,
                    'new_department' => $newDepartment,
                    'office_id' => $office->id,
                    'office_name' => $office->name
                ]);

                return true;
            }

            return false; // No sync needed

        } catch (\Exception $e) {
            Log::error('Failed to synchronize employee department', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Synchronize department field for all employees to match their assigned offices
     */
    public function synchronizeAllDepartments(): array
    {
        $results = [
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        $employees = Employee::whereNotNull('office_id')
            ->with('office')
            ->get();

        foreach ($employees as $employee) {
            $results['processed']++;

            try {
                $wasUpdated = $this->synchronizeEmployeeDepartment($employee);
                if ($wasUpdated) {
                    $results['updated']++;
                } else {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('Department synchronization completed', $results);

        return $results;
    }

    /**
     * Find employees with inconsistent department/office assignments
     */
    public function findInconsistentEmployees(): array
    {
        $inconsistent = [];

        $employees = Employee::whereNotNull('office_id')
            ->with('office')
            ->get();

        foreach ($employees as $employee) {
            if ($employee->office && $employee->department !== $employee->office->name) {
                $inconsistent[] = [
                    'employee' => $employee,
                    'current_department' => $employee->department,
                    'expected_department' => $employee->office->name,
                    'office_id' => $employee->office_id,
                    'office_name' => $employee->office->name
                ];
            }
        }

        return $inconsistent;
    }

    /**
     * Get summary of department synchronization status
     */
    public function getSyncSummary(): array
    {
        $totalEmployees = Employee::whereNotNull('office_id')->count();
        $inconsistentEmployees = count($this->findInconsistentEmployees());
        $consistentEmployees = $totalEmployees - $inconsistentEmployees;

        return [
            'total_employees' => $totalEmployees,
            'consistent_employees' => $consistentEmployees,
            'inconsistent_employees' => $inconsistentEmployees,
            'consistency_rate' => $totalEmployees > 0 ? round(($consistentEmployees / $totalEmployees) * 100, 2) : 100
        ];
    }

    /**
     * Validate employee department consistency
     */
    public function validateEmployeeConsistency(Employee $employee): array
    {
        $issues = [];

        if (!$employee->office) {
            $issues[] = 'Employee has no assigned office';
            return [
                'is_consistent' => false,
                'issues' => $issues
            ];
        }

        if ($employee->department !== $employee->office->name) {
            $issues[] = sprintf(
                'Department field (%s) does not match assigned office (%s)',
                $employee->department,
                $employee->office->name
            );
        }

        return [
            'is_consistent' => empty($issues),
            'issues' => $issues
        ];
    }
}