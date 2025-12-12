<?php

namespace App\Http\Controllers;

use App\Exports\ArchiveExport;
use App\Models\Employee;
use App\Models\User;
use App\Services\ArchiveService;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ArchiveController extends Controller
{
    public function __construct(
        private ArchiveService $archiveService,
        private AuditTrailService $auditTrailService
    ) {}

    /**
     * Display list of archived employees
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'archived_by' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $perPage = $filters['per_page'] ?? 15;
        $archivedEmployees = $this->archiveService->getArchivedEmployees($filters, $perPage);
        $stats = $this->archiveService->getArchiveStats();

        // Get users for filter dropdown
        $usersWithArchives = User::whereHas('archivedEmployees')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('employees.archive.index', compact(
            'archivedEmployees',
            'stats',
            'filters',
            'usersWithArchives'
        ));
    }

    /**
     * Display archived employee details
     */
    public function show(int $id)
    {
        // Find the employee including soft-deleted records
        $employee = Employee::withTrashed()->findOrFail($id);

        // Ensure we're looking at a trashed employee
        if (!$employee->trashed()) {
            abort(404, 'Employee not found in archives');
        }

        $employee->load([
            'user',
            'archivedBy',
            'workExperiences',
            'education',
            'familyBackground',
            'children',
            'trainings',
            'civilServiceEligibilities',
            'voluntaryWork',
            'otherInformation',
            'references',
            'questionnaire'
        ]);

        return view('employees.archive.show', compact('employee'));
    }

    /**
     * Restore an archived employee
     */
    public function restore(int $id, Request $request): JsonResponse
    {
        // Find the employee including soft-deleted records
        $employee = Employee::withTrashed()->findOrFail($id);

        // Ensure we're working with a trashed employee
        if (!$employee->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Employee is not archived and cannot be restored.'
            ], 400);
        }

        // Check if employee can be restored
        if (!$this->archiveService->canRestore($employee)) {
            return response()->json([
                'success' => false,
                'message' => 'This employee cannot be restored at this time.'
            ], 400);
        }

        try {
            $restoredEmployee = $this->archiveService->restoreEmployee($employee, Auth::user());

            return response()->json([
                'success' => true,
                'message' => "Employee {$restoredEmployee->first_name} {$restoredEmployee->last_name} has been restored successfully.",
                'employee' => $restoredEmployee,
                'redirect_url' => route('employees.show', $restoredEmployee->id)
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to restore employee', [
                'employee_id' => $employee->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore employee. Please try again or contact support.'
            ], 500);
        }
    }

    /**
     * Archive the specified employee.
     */
    public function destroy(Employee $employee)
    {
        $this->authorize('employee.delete');

        // Check if employee can be archived using new validation
        $validation = $this->archiveService->validateArchivalReadiness($employee);
        
        if (!$validation['can_archive']) {
             $errorMessage = 'Cannot archive employee: ' . implode(' ', $validation['blocking_issues']);
             return redirect()->route('employees.index')
                ->with('error', $errorMessage);
        }

        try {
            $archivedEmployee = $this->archiveService->archiveEmployee($employee, auth()->user());
            
            $message = "Employee {$archivedEmployee->first_name} {$archivedEmployee->last_name} has been archived successfully.";
            
            // Add warnings if any
            if (!empty($validation['warnings'])) {
                $message .= " Note: " . implode(' ', $validation['warnings']);
            }

            return redirect()->route('employees.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to archive employee', [
                'employee_id' => $employee->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('employees.index')
                ->with('error', 'Failed to archive employee: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete an archived employee (Super Admin only)
     */
    public function forceDelete(int $id, Request $request): JsonResponse
    {
        // Find the employee including soft-deleted records
        $employee = Employee::withTrashed()->findOrFail($id);

        // Ensure we're working with a trashed employee
        if (!$employee->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Employee is not archived and cannot be permanently deleted.'
            ], 400);
        }

        // Additional confirmation required
        $request->validate([
            'confirmation' => ['required', 'string', Rule::in(['PERMANENTLY_DELETE_' . $employee->id])],
            'employee_name' => ['required', 'string', function ($attribute, $value, $fail) use ($employee) {
                if ($value !== ($employee->first_name . ' ' . $employee->last_name)) {
                    $fail('The employee name confirmation does not match.');
                }
            }],
        ]);

        try {
            $this->archiveService->permanentDelete($employee, Auth::user());

            return response()->json([
                'success' => true,
                'message' => "Employee {$employee->first_name} {$employee->last_name} has been permanently deleted."
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to permanently delete employee', [
                'employee_id' => $employee->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete employee. Please try again or contact support.'
            ], 500);
        }
    }

    /**
     * Get archive statistics (AJAX endpoint)
     */
    public function stats(): JsonResponse
    {
        $stats = $this->archiveService->getArchiveStats();

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Export archived employees list
     */
    public function export(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'archived_by' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        // Get all archived employees (no pagination for export)
        $archivedEmployees = $this->archiveService->getArchivedEmployees($filters, 1000);

        // Load archivedBy relationship for better export data
        $archivedEmployees->getCollection()->each(function ($employee) {
            $employee->load(['archivedBy']);
        });

        // Generate filename
        $filename = 'archived_employees_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Log export action
        $this->auditTrailService->log(
            'export_archived_employees',
            Auth::id(),
            'Exported archived employees list: ' . $archivedEmployees->count() . ' records to ' . $filename
        );

        return Excel::download(new ArchiveExport($archivedEmployees), $filename);
    }

    /**
     * Bulk restore multiple archived employees
     */
    public function bulkRestore(Request $request): JsonResponse
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'required|exists:employees,id',
        ]);

        $employeeIds = $request->employee_ids;
        $restoredCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($employeeIds as $employeeId) {
            $employee = Employee::withTrashed()->find($employeeId);

            if (!$employee || !$employee->trashed()) {
                $failedCount++;
                $errors[] = "Employee ID {$employeeId} is not archived or does not exist.";
                continue;
            }

            try {
                if ($this->archiveService->canRestore($employee)) {
                    $this->archiveService->restoreEmployee($employee, Auth::user());
                    $restoredCount++;
                } else {
                    $failedCount++;
                    $errors[] = "Employee {$employee->first_name} {$employee->last_name} cannot be restored at this time.";
                }
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Failed to restore employee {$employee->first_name} {$employee->last_name}: " . $e->getMessage();
            }
        }

        $message = "Bulk restore completed. {$restoredCount} employees restored successfully.";
        if ($failedCount > 0) {
            $message .= " {$failedCount} operations failed.";
        }

        // Log the bulk restore operation
        $this->auditTrailService->logBulkActivity('bulk_restore', [
            'total_requested' => count($employeeIds),
            'restored_count' => $restoredCount,
            'failed_count' => $failedCount,
            'employee_ids' => $employeeIds,
            'errors' => $errors
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'restored_count' => $restoredCount,
            'failed_count' => $failedCount,
            'errors' => $errors
        ]);
    }
}