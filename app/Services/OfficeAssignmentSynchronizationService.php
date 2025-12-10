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
        $results = [
            'deactivated' => [],
            'created' => [],
            'updated' => [],
            'errors' => []
        ];

        $officeId = $newOfficeId ?? $employee->office_id;
        if (!$officeId) {
            $results['errors'][] = 'Cannot synchronize without a target office ID.';
            return $results;
        }

        $userId = $employee->user?->id;
        if (!$userId) {
            $results['errors'][] = 'Employee does not have an associated user account.';
            return $results;
        }

        DB::beginTransaction();
        try {
            // Get current active assignments tied to this employee or user
            $activeAssignments = OfficeAssignment::where('is_active', true)
                ->where(function ($query) use ($employee, $userId) {
                    $query->where('employee_id', $employee->id)
                        ->orWhere('user_id', $userId);
                })
                ->get();

            // Deactivate assignments in different offices
            foreach ($activeAssignments as $assignment) {
                if ($assignment->office_id !== $officeId) {
                    try {
                        $assignment->update([
                            'is_active' => false,
                            'ended_date' => now()->toDateString(),
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
            $existingAssignment = OfficeAssignment::where('user_id', $userId)
                ->where('office_id', $officeId)
                ->first();

            // Determine role - explicit role takes priority over automatic assignment
            $role = 'Member';
            if ($explicitRole !== null) {
                $role = $explicitRole;
            } elseif ($employee->is_department_head) {
                $role = 'Department Head';
            }

            if ($existingAssignment) {
                $existingAssignment->update([
                    'employee_id' => $employee->id,
                    'role' => $role,
                    'is_active' => true,
                    'assigned_date' => now()->toDateString(),
                    'ended_date' => null,
                ]);

                $results['updated'][] = $existingAssignment->id;
            } else {
                $newAssignment = OfficeAssignment::create([
                    'user_id' => $userId,
                    'employee_id' => $employee->id,
                    'office_id' => $officeId,
                    'role' => $role,
                    'is_active' => true,
                    'assigned_date' => now()->toDateString(),
                    'assigned_by' => auth()->id(),
                ]);

                $results['created'][] = $newAssignment->id;

                Log::info('Created new office assignment', [
                    'employee_id' => $employee->id,
                    'assignment_id' => $newAssignment->id,
                    'office_id' => $officeId,
                    'role' => $role
                ]);
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
