<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Office;
use App\Models\OfficeAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfficeAssignmentSynchronizationService
{
    /**
     * Synchronize office assignments for a specific employee
     */
    public function synchronizeEmployee(Employee $employee, ?int $newOfficeId = null, ?string $explicitRole = null): array
    {
        $officeId = $newOfficeId ?? $employee->office_id;
        $results = [
            'deactivated' => [],
            'created' => [],
            'updated' => [],
            'errors' => []
        ];

        DB::beginTransaction();
        try {
            // Get current active assignments for this employee
            $activeAssignments = OfficeAssignment::where('employee_id', $employee->id)
                ->where('is_active', true)
                ->get();

            // Deactivate assignments in different offices
            foreach ($activeAssignments as $assignment) {
                if ($assignment->office_id !== $officeId) {
                    try {
                        $assignment->update([
                            'is_active' => false,
                            'end_date' => now(),
                            'updated_by' => auth()->id()
                        ]);
                        $results['deactivated'][] = $assignment->id;

                        Log::info('Deactivated office assignment', [
                            'employee_id' => $employee->id,
                            'assignment_id' => $assignment->id,
                            'old_office' => $assignment->office_id,
                            'new_office' => $officeId
                        ]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Handle constraint violation - try to delete instead of deactivating
                        if (str_contains($e->getMessage(), 'Duplicate entry')) {
                            try {
                                $assignment->delete();
                                $results['deactivated'][] = $assignment->id;

                                Log::info('Deleted office assignment due to constraint', [
                                    'employee_id' => $employee->id,
                                    'assignment_id' => $assignment->id,
                                    'old_office' => $assignment->office_id,
                                    'new_office' => $officeId
                                ]);
                            } catch (\Exception $deleteException) {
                                $results['errors'][] = "Could not delete assignment {$assignment->id}: " . $deleteException->getMessage();
                            }
                        } else {
                            $results['errors'][] = "Could not deactivate assignment {$assignment->id}: " . $e->getMessage();
                        }
                    }
                }
            }

            // Check if employee already has an active assignment in the target office
            $existingAssignment = OfficeAssignment::where('employee_id', $employee->id)
                ->where('office_id', $officeId)
                ->where('is_active', true)
                ->first();

            if (!$existingAssignment) {
                // Create new assignment in the target office
                $office = Office::findOrFail($officeId);

                // Determine role - explicit role takes priority over automatic assignment
                $role = 'Member'; // Default role
                if ($explicitRole !== null) {
                    // Explicit role from form input always takes priority
                    $role = $explicitRole;
                } elseif ($employee->is_department_head) {
                    // Fall back to current employee status only if no explicit role provided
                    $role = 'Department Head';
                }

                $newAssignment = OfficeAssignment::create([
                    'user_id' => $employee->user?->id ?? null,
                    'employee_id' => $employee->id,
                    'office_id' => $officeId,
                    'role' => $role,
                    'is_active' => true,
                    'assigned_date' => now()->toDateString(),
                    'start_date' => now(),
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id()
                ]);

                $results['created'][] = $newAssignment->id;

                Log::info('Created new office assignment', [
                    'employee_id' => $employee->id,
                    'assignment_id' => $newAssignment->id,
                    'office_id' => $officeId,
                    'role' => $role
                ]);
            } else {
                $results['updated'][] = $existingAssignment->id;
            }

            // Update user's office if exists
            if ($employee->user) {
                $employee->user->update(['office_id' => $officeId]);
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();

            Log::error('Failed to synchronize office assignments', [
                'employee_id' => $employee->id,
                'office_id' => $officeId,
                'error' => $e->getMessage()
            ]);

            return $results;
        }
    }

    /**
     * Find all employees with inconsistent office assignments
     */
    public function findInconsistentAssignments(): array
    {
        $inconsistent = [];

        $employees = Employee::with(['activeOfficeAssignments.office', 'office'])
            ->whereNotNull('office_id')
            ->get();

        foreach ($employees as $employee) {
            $hasIssues = false;
            $issues = [];

            // Check if employee has active assignments in different office
            $assignmentsInDifferentOffice = $employee->activeOfficeAssignments
                ->filter(function ($assignment) use ($employee) {
                    return $assignment->office_id !== $employee->office_id;
                });

            if ($assignmentsInDifferentOffice->count() > 0) {
                $hasIssues = true;
                $issues[] = 'Has active assignments in different office';
                foreach ($assignmentsInDifferentOffice as $assignment) {
                    $issues[] = "Active in {$assignment->office->name} (ID: {$assignment->id})";
                }
            }

            // Check if employee has no active assignments
            if ($employee->activeOfficeAssignments->count() === 0) {
                $hasIssues = true;
                $issues[] = 'No active office assignments';
            }

            // Check if employee's department head status matches assignments
            $activeDepartmentHeadAssignment = $employee->activeOfficeAssignments
                ->where('role', 'Department Head')
                ->where('is_active', true)
                ->first();

            if ($employee->is_department_head && !$activeDepartmentHeadAssignment) {
                $hasIssues = true;
                $issues[] = 'Marked as department head but no active Department Head assignment';
            }

            if (!$employee->is_department_head && $activeDepartmentHeadAssignment) {
                $hasIssues = true;
                $issues[] = 'Has Department Head assignment but not marked as department head';
            }

            if ($hasIssues) {
                $inconsistent[] = [
                    'employee' => $employee,
                    'issues' => $issues,
                    'employee_office' => $employee->office?->name,
                    'employee_office_id' => $employee->office_id,
                    'active_assignments' => $employee->activeOfficeAssignments->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'office' => $assignment->office->name,
                            'role' => $assignment->role,
                            'is_active' => $assignment->is_active
                        ];
                    })->toArray()
                ];
            }
        }

        return $inconsistent;
    }

    /**
     * Synchronize all inconsistent office assignments
     */
    public function synchronizeAll(array $options = []): array
    {
        $dryRun = $options['dry_run'] ?? false;
        $results = [
            'processed' => 0,
            'deactivated' => [],
            'created' => [],
            'updated' => [],
            'errors' => [],
            'dry_run' => $dryRun
        ];

        $inconsistentEmployees = $this->findInconsistentAssignments();

        foreach ($inconsistentEmployees as $inconsistent) {
            $employee = $inconsistent['employee'];

            if ($dryRun) {
                $results['processed']++;
                $results['dry_run_results'][] = [
                    'employee' => $employee->full_name,
                    'employee_id' => $employee->id,
                    'issues' => $inconsistent['issues'],
                    'would_sync_to' => $employee->office_id
                ];
                continue;
            }

            $syncResult = $this->synchronizeEmployee($employee);
            $results['processed']++;

            $results['deactivated'] = array_merge($results['deactivated'], $syncResult['deactivated']);
            $results['created'] = array_merge($results['created'], $syncResult['created']);
            $results['updated'] = array_merge($results['updated'], $syncResult['updated']);
            $results['errors'] = array_merge($results['errors'], $syncResult['errors']);
        }

        return $results;
    }

    /**
     * Get summary of office assignment consistency
     */
    public function getConsistencySummary(): array
    {
        $totalEmployees = Employee::whereNotNull('office_id')->count();
        $inconsistentCount = count($this->findInconsistentAssignments());
        $consistentCount = $totalEmployees - $inconsistentCount;

        $offices = Office::withCount(['activeAssignments'])
            ->get()
            ->map(function ($office) {
                // Count employees directly in employees table
                $employeeCount = Employee::where('office_id', $office->id)->count();
                return [
                    'id' => $office->id,
                    'name' => $office->name,
                    'employee_count' => $employeeCount,
                    'assignment_count' => $office->active_assignments_count,
                    'is_consistent' => $employeeCount === $office->active_assignments_count
                ];
            });

        return [
            'total_employees' => $totalEmployees,
            'consistent_employees' => $consistentCount,
            'inconsistent_employees' => $inconsistentCount,
            'consistency_rate' => $totalEmployees > 0 ? round(($consistentCount / $totalEmployees) * 100, 2) : 100,
            'offices' => $offices,
            'problematic_offices' => $offices->filter(fn($office) => !$office['is_consistent'])->values()
        ];
    }
}