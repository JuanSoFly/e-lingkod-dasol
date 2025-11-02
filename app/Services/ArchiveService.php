<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\Activity;

class ArchiveService
{
    private AuditTrailService $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Archive an employee and their associated user account
     */
    public function archiveEmployee(Employee $employee, ?User $archivedBy = null): Employee
    {
        return DB::transaction(function () use ($employee, $archivedBy) {
            try {
                // Archive the employee record
                $employee->update([
                    'archived_at' => now(),
                    'archived_by' => $archivedBy?->id,
                    'updated_at' => now(),
                ]);

                // Soft delete the employee
                $employee->delete();

                // Soft delete associated user account if exists
                if ($employee->user) {
                    $employee->user->delete();
                }

                // Log the archive operation
                $this->auditTrailService->logEmployeeArchive($employee, $archivedBy, [
                    'reason' => 'Employee archive operation'
                ]);

                Log::info('Employee archived successfully', [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'archived_by' => $archivedBy?->id,
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                ]);

                return $employee;

            } catch (\Exception $e) {
                Log::error('Failed to archive employee', [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'archived_by' => $archivedBy?->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Restore an archived employee and their user account
     */
    public function restoreEmployee(Employee $employee, ?User $restoredBy = null): Employee
    {
        return DB::transaction(function () use ($employee, $restoredBy) {
            try {
                // Restore the employee record
                $employee->restore();

                // Clear archive fields
                $employee->update([
                    'archived_at' => null,
                    'archived_by' => null,
                    'updated_at' => now(),
                ]);

                // Restore associated user account if exists
                if ($employee->user) {
                    $employee->user->restore();
                }

                // Log the restore operation
                $this->auditTrailService->logEmployeeRestore($employee, $restoredBy, [
                    'reason' => 'Employee restore operation'
                ]);

                Log::info('Employee restored successfully', [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'restored_by' => $restoredBy?->id,
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                ]);

                return $employee;

            } catch (\Exception $e) {
                Log::error('Failed to restore employee', [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'restored_by' => $restoredBy?->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Permanently delete an archived employee (only for Super Admin)
     */
    public function permanentDelete(Employee $employee, ?User $deletedBy = null): bool
    {
        return DB::transaction(function () use ($employee, $deletedBy) {
            try {
                // Ensure employee is archived before permanent deletion
                if (!$employee->trashed()) {
                    throw new \Exception('Cannot permanently delete an active employee. Archive the employee first.');
                }

                // Get employee details for logging before deletion
                $employeeDetails = [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                ];

                // Force delete the employee (will cascade delete related records)
                $employee->forceDelete();

                // Log the permanent deletion
                $this->auditTrailService->logEmployeeActivity('permanently_deleted', $employee, [
                    'employee_details' => $employeeDetails,
                    'deleted_by' => $deletedBy?->id,
                    'action_type' => 'permanent_delete',
                    'reason' => 'Permanent deletion of archived employee'
                ]);

                Log::warning('Employee permanently deleted', [
                    'employee_details' => $employeeDetails,
                    'deleted_by' => $deletedBy?->id,
                ]);

                return true;

            } catch (\Exception $e) {
                Log::error('Failed to permanently delete employee', [
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'deleted_by' => $deletedBy?->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Get archived employees with filtering and pagination
     */
    public function getArchivedEmployees(array $filters = [], int $perPage = 15)
    {
        $query = Employee::onlyTrashed()
            ->with(['user', 'archivedBy'])
            ->orderBy('archived_at', 'desc');

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (!empty($filters['position'])) {
            $query->where('position', $filters['position']);
        }

        if (!empty($filters['archived_by'])) {
            $query->where('archived_by', $filters['archived_by']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('archived_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('archived_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get archive statistics
     */
    public function getArchiveStats(): array
    {
        $totalArchived = Employee::onlyTrashed()->count();
        $archivedThisMonth = Employee::onlyTrashed()
            ->whereMonth('archived_at', now()->month)
            ->whereYear('archived_at', now()->year)
            ->count();

        $archivedByUsers = Employee::onlyTrashed()
            ->with('archivedBy')
            ->get()
            ->groupBy('archivedBy.name')
            ->map(fn($group) => $group->count())
            ->toArray();

        $recentArchives = Employee::onlyTrashed()
            ->with('archivedBy')
            ->orderBy('archived_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_archived' => $totalArchived,
            'archived_this_month' => $archivedThisMonth,
            'archived_by_users' => $archivedByUsers,
            'recent_archives' => $recentArchives,
        ];
    }

    /**
     * Check if employee can be archived
     */
    public function canArchive(Employee $employee): bool
    {
        // Cannot archive already archived employee
        if ($employee->trashed()) {
            return false;
        }

        // Add any additional business rules here
        // For example: check if employee has pending tasks, etc.

        return true;
    }

    /**
     * Check if employee can be restored
     */
    public function canRestore(Employee $employee): bool
    {
        // Can only restore archived employees
        if (!$employee->trashed()) {
            return false;
        }

        // Add any additional business rules here
        // For example: check if email conflicts exist

        return true;
    }
}