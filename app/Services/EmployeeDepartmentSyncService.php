<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OfficeAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeDepartmentSyncService
{
    /**
     * Sync employee's department information based on their active office assignment
     */
    public function syncEmployeeDepartment(Employee|int $employeeId): bool
    {
        try {
            $employee = is_int($employeeId) ? Employee::findOrFail($employeeId) : $employeeId;

            // Get the most recent active office assignment
            $activeAssignment = OfficeAssignment::where('employee_id', $employee->id)
                ->where('is_active', true)
                ->with('office')
                ->latest('assigned_date')
                ->first();

            if ($activeAssignment && $activeAssignment->office) {
                $departmentName = $activeAssignment->office->name;

                // Update employee department
                $employee->department = $departmentName;
                $employee->saveQuietly(); // Save without triggering events

                // Update associated user department if exists
                if ($employee->user) {
                    $employee->user->department = $departmentName;
                    $employee->user->saveQuietly();
                }

                Log::info('Department synchronized for employee', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'department' => $departmentName,
                    'office_id' => $activeAssignment->office_id
                ]);

                return true;
            } else {
                // No active assignment found, clear department
                $employee->department = null;
                $employee->saveQuietly();

                if ($employee->user) {
                    $employee->user->department = null;
                    $employee->user->saveQuietly();
                }

                Log::warning('No active assignment found for employee, department cleared', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync employee department', [
                'employee_id' => is_int($employeeId) ? $employeeId : $employeeId->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Sync departments for all employees with active assignments
     */
    public function syncAllDepartments(): array
    {
        $results = [
            'synced' => 0,
            'failed' => 0,
            'no_assignment' => 0,
            'errors' => []
        ];

        $employees = Employee::whereNull('archived_at')->get();

        foreach ($employees as $employee) {
            try {
                $syncResult = $this->syncEmployeeDepartment($employee);

                if ($syncResult) {
                    $results['synced']++;
                } else {
                    $results['no_assignment']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('Department synchronization completed', $results);

        return $results;
    }

    /**
     * Sync department when office assignment is created
     */
    public function syncOnAssignmentCreate(OfficeAssignment $assignment): void
    {
        $this->syncEmployeeDepartment($assignment->employee_id);
    }

    /**
     * Sync department when office assignment is updated
     */
    public function syncOnAssignmentUpdate(OfficeAssignment $assignment): void
    {
        $this->syncEmployeeDepartment($assignment->employee_id);
    }

    /**
     * Sync department when office assignment is deleted
     */
    public function syncOnAssignmentDelete(OfficeAssignment $assignment): void
    {
        $this->syncEmployeeDepartment($assignment->employee_id);
    }

    /**
     * Validate department consistency across employee and user tables
     */
    public function validateDepartmentConsistency(): array
    {
        $inconsistencies = [];

        $employees = Employee::with('user')->whereNull('archived_at')->get();

        foreach ($employees as $employee) {
            if ($employee->user && $employee->department !== $employee->user->department) {
                $inconsistencies[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'employee_department' => $employee->department,
                    'user_department' => $employee->user->department
                ];
            }
        }

        return $inconsistencies;
    }

    /**
     * Fix department inconsistencies between employee and user tables
     */
    public function fixDepartmentInconsistencies(): array
    {
        $inconsistencies = $this->validateDepartmentConsistency();
        $fixed = 0;

        foreach ($inconsistencies as $inconsistency) {
            try {
                $employee = Employee::findOrFail($inconsistency['employee_id']);

                // Use employee department as source of truth and sync to user
                if ($employee->user) {
                    $employee->user->department = $employee->department;
                    $employee->user->saveQuietly();
                    $fixed++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to fix department inconsistency', [
                    'employee_id' => $inconsistency['employee_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'found' => count($inconsistencies),
            'fixed' => $fixed
        ];
    }
}