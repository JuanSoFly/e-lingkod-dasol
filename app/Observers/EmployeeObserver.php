<?php

namespace App\Observers;

use App\Models\Employee;
use App\Services\DepartmentSyncService;
use App\Services\IdentityLinker;
use App\Services\OfficeAssignmentSynchronizationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee): void
    {
        if ($employee->wasChanged(['email', 'user_id'])) {
            $this->syncIdentityLink($employee);
        }

        // Check if office_id was changed
        if ($employee->wasChanged('office_id')) {
            $oldOfficeId = $employee->getOriginal('office_id');
            $newOfficeId = $employee->office_id;

            // Only sync if we have a valid new office ID and it's different from old
            if ($newOfficeId && $oldOfficeId !== $newOfficeId) {
                try {
                    $syncService = app(OfficeAssignmentSynchronizationService::class);
                    $result = $syncService->synchronizeEmployee($employee, $newOfficeId);

                    Log::info('Employee office synchronized via observer', [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                        'old_office_id' => $oldOfficeId,
                        'new_office_id' => $newOfficeId,
                        'sync_result' => $result,
                        'updated_by' => Auth::id()
                    ]);

                    // Also synchronize department field to match new office
                    $departmentSyncService = app(DepartmentSyncService::class);
                    $departmentSyncService->synchronizeEmployeeDepartment($employee);

                } catch (\Exception $e) {
                    Log::error('Failed to synchronize employee office assignments via observer', [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                        'old_office_id' => $oldOfficeId,
                        'new_office_id' => $newOfficeId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Optionally re-throw if you want the transaction to fail
                    // throw $e;
                }
            }
        }

        // Check if department head status was changed
        if ($employee->wasChanged('is_department_head')) {
            $this->handleDepartmentHeadChange($employee);
        }
    }

    /**
     * Handle department head status changes
     */
    private function handleDepartmentHeadChange(Employee $employee): void
    {
        try {
            $isDepartmentHead = $employee->is_department_head;
            $activeAssignment = $employee->activeOfficeAssignments()
                ->where('office_id', $employee->office_id)
                ->first();

            if ($activeAssignment) {
                // Update role on existing assignment
                $newRole = $isDepartmentHead ? 'Department Head' : 'Member';

                if ($activeAssignment->role !== $newRole) {
                    $activeAssignment->update([
                        'role' => $newRole,
                        'updated_by' => Auth::id()
                    ]);

                    Log::info('Employee department head role updated via observer', [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                        'assignment_id' => $activeAssignment->id,
                        'old_role' => $activeAssignment->role,
                        'new_role' => $newRole,
                        'updated_by' => Auth::id()
                    ]);
                }
            } else {
                // No active assignment exists, create one
                $syncService = app(OfficeAssignmentSynchronizationService::class);
                $result = $syncService->synchronizeEmployee($employee, $employee->office_id);

                Log::info('Created office assignment for department head status change', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'office_id' => $employee->office_id,
                    'is_department_head' => $isDepartmentHead,
                    'sync_result' => $result,
                    'updated_by' => Auth::id()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle department head status change', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'is_department_head' => $employee->is_department_head,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        // Ensure new employees have proper office assignments
        if ($employee->office_id && $employee->activeOfficeAssignments->count() === 0) {
            try {
                $syncService = app(OfficeAssignmentSynchronizationService::class);
                $result = $syncService->synchronizeEmployee($employee, $employee->office_id);

                Log::info('Office assignment created for new employee', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'office_id' => $employee->office_id,
                    'sync_result' => $result,
                    'created_by' => Auth::id()
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to create office assignment for new employee', [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->full_name,
                    'office_id' => $employee->office_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->syncIdentityLink($employee);
    }

    /**
     * Handle the Employee "deleted" event.
     */
    public function deleted(Employee $employee): void
    {
        try {
            app(IdentityLinker::class)->detachEmployee($employee);
        } catch (\Throwable $e) {
            Log::error('Failed to detach employee identity relationship', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Employee "restored" event.
     */
    public function restored(Employee $employee): void
    {
        $this->syncIdentityLink($employee);

        if (!$employee->office_id) {
            return;
        }

        try {
            app(OfficeAssignmentSynchronizationService::class)->synchronizeEmployee($employee, $employee->office_id);
        } catch (\Throwable $e) {
            Log::error('Failed to synchronize office assignments after employee restore', [
                'employee_id' => $employee->id,
                'office_id' => $employee->office_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncIdentityLink(Employee $employee): void
    {
        try {
            app(IdentityLinker::class)->syncForEmployee($employee);
        } catch (\Throwable $e) {
            Log::error('Failed to synchronize employee identity link', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
