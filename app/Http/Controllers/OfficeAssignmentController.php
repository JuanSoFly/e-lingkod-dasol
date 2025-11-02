<?php

namespace App\Http\Controllers;

use App\Models\OfficeAssignment;
use App\Models\Office;
use App\Models\User;
use App\Models\Employee;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class OfficeAssignmentController extends Controller
{
    private AuditTrailService $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->middleware(['auth', 'role:Super Admin|HR Admin']);
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Display a listing of office assignments.
     */
    public function index(Request $request): View
    {
        $query = OfficeAssignment::with(['user', 'office'])
            ->orderBy('office_id')
            ->orderBy('role');

        // Filter by office if specified
        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        // Filter by role if specified
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $assignments = $query->paginate(15);
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $roles = [
            'Department Head' => 'Department Head',
            'Assessor' => 'Assessor (PMT)',
            'Final Approver' => 'Final Approver (Mayor)',
        ];

        return view('admin.office-assignments.index', compact(
            'assignments',
            'offices',
            'roles'
        ));
    }

    /**
     * Show the form for creating a new office assignment.
     */
    public function create(): View
    {
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $users = User::with(['roles', 'employee'])
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'HR Admin', 'Employee']);
            })
            ->orderBy('email')
            ->get();

        $roles = [
            'Department Head' => 'Department Head - Manages office OPCR creation and updates',
            'Assessor' => 'Assessor (PMT) - Evaluates OPCR performance',
            'Final Approver' => 'Final Approver (Mayor) - Gives final approval',
        ];

        return view('admin.office-assignments.create', compact(
            'offices',
            'users',
            'roles'
        ));
    }

    /**
     * Store a newly created office assignment in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'office_id' => 'required|exists:offices,id',
            'role' => 'required|in:Department Head,Assessor,Final Approver',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ], [
            'user_id.required' => 'Please select a user',
            'office_id.required' => 'Please select an office',
            'role.required' => 'Please select a role',
            'role.in' => 'Invalid role selected',
        ]);

        // Check if assignment already exists
        $existingAssignment = OfficeAssignment::where('user_id', $validated['user_id'])
            ->where('office_id', $validated['office_id'])
            ->where('role', $validated['role'])
            ->first();

        if ($existingAssignment) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This user is already assigned to this office with the same role.');
        }

        try {
            DB::transaction(function () use ($validated) {
                $assignment = OfficeAssignment::create([
                    'user_id' => $validated['user_id'],
                    'office_id' => $validated['office_id'],
                    'role' => $validated['role'],
                    'is_active' => $validated['is_active'] ?? true,
                    'notes' => $validated['notes'] ?? null,
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                ]);

                // Log the assignment creation
                $this->auditTrailService->logOfficeAssignment(
                    'office_assignment_created',
                    $assignment,
                    $assignment->office,
                    $assignment->user->employee,
                    [
                        'user_email' => $assignment->user->email,
                    ]
                );

                // Log user activity
                Log::info('Office assignment created', [
                    'assignment_id' => $assignment->id,
                    'user_id' => $assignment->user_id,
                    'office_id' => $assignment->office_id,
                    'role' => $assignment->role,
                    'created_by' => auth()->id(),
                    'timestamp' => now()->toDateTimeString(),
                ]);
            });

            return redirect()->route('admin.office-assignments.index')
                ->with('success', 'Office assignment created successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to create office assignment', [
                'error' => $e->getMessage(),
                'validated_data' => $validated,
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create office assignment. Please try again.');
        }
    }

    /**
     * Display the specified office assignment.
     */
    public function show(OfficeAssignment $officeAssignment): View
    {
        $officeAssignment->load(['user', 'office', 'assignedByUser']);

        return view('admin.office-assignments.show', compact('officeAssignment'));
    }

    /**
     * Show the form for editing the specified office assignment.
     */
    public function edit(OfficeAssignment $officeAssignment): View
    {
        $officeAssignment->load(['user', 'office']);

        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $users = User::with(['roles', 'employee'])
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'HR Admin', 'Employee']);
            })
            ->orderBy('email')
            ->get();

        $roles = [
            'Department Head' => 'Department Head - Manages office OPCR creation and updates',
            'Assessor' => 'Assessor (PMT) - Evaluates OPCR performance',
            'Final Approver' => 'Final Approver (Mayor) - Gives final approval',
        ];

        return view('admin.office-assignments.edit', compact(
            'officeAssignment',
            'offices',
            'users',
            'roles'
        ));
    }

    /**
     * Update the specified office assignment in storage.
     */
    public function update(Request $request, OfficeAssignment $officeAssignment): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'office_id' => 'required|exists:offices,id',
            'role' => 'required|in:Department Head,Assessor,Final Approver',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ], [
            'user_id.required' => 'Please select a user',
            'office_id.required' => 'Please select an office',
            'role.required' => 'Please select a role',
            'role.in' => 'Invalid role selected',
        ]);

        // Check if another assignment with same combination exists
        $conflictingAssignment = OfficeAssignment::where('user_id', $validated['user_id'])
            ->where('office_id', $validated['office_id'])
            ->where('role', $validated['role'])
            ->where('id', '!=', $officeAssignment->id)
            ->first();

        if ($conflictingAssignment) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Another assignment with this combination already exists.');
        }

        try {
            DB::transaction(function () use ($validated, $officeAssignment) {
                $oldData = $officeAssignment->toArray();

                $officeAssignment->update([
                    'user_id' => $validated['user_id'],
                    'office_id' => $validated['office_id'],
                    'role' => $validated['role'],
                    'is_active' => $validated['is_active'] ?? $officeAssignment->is_active,
                    'notes' => $validated['notes'] ?? $officeAssignment->notes,
                    'updated_by' => auth()->id(),
                ]);

                // Log the assignment update
                $this->auditTrailService->logOfficeAssignment(
                    'office_assignment_updated',
                    $officeAssignment,
                    $officeAssignment->office,
                    $officeAssignment->user->employee,
                    [
                        'old_data' => $oldData,
                        'new_data' => $validated,
                    ]
                );

                // Log user activity
                Log::info('Office assignment updated', [
                    'assignment_id' => $officeAssignment->id,
                    'user_id' => $officeAssignment->user_id,
                    'office_id' => $officeAssignment->office_id,
                    'role' => $officeAssignment->role,
                    'updated_by' => auth()->id(),
                    'timestamp' => now()->toDateTimeString(),
                ]);
            });

            return redirect()->route('admin.office-assignments.index')
                ->with('success', 'Office assignment updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update office assignment', [
                'error' => $e->getMessage(),
                'assignment_id' => $officeAssignment->id,
                'validated_data' => $validated,
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update office assignment. Please try again.');
        }
    }

    /**
     * Remove the specified office assignment from storage.
     */
    public function destroy(OfficeAssignment $officeAssignment): RedirectResponse
    {
        try {
            DB::transaction(function () use ($officeAssignment) {
                $assignmentData = $officeAssignment->toArray();

                $officeAssignment->delete();

                // Log the assignment deletion
                $this->auditTrailService->logOfficeAssignment(
                    'office_assignment_deleted',
                    $officeAssignment,
                    $officeAssignment->office,
                    $officeAssignment->user->employee,
                    [
                        'assignment_data' => $assignmentData,
                    ]
                );

                // Log user activity
                Log::info('Office assignment deleted', [
                    'assignment_id' => $officeAssignment->id,
                    'user_id' => $officeAssignment->user_id,
                    'office_id' => $officeAssignment->office_id,
                    'role' => $officeAssignment->role,
                    'deleted_by' => auth()->id(),
                    'timestamp' => now()->toDateTimeString(),
                ]);
            });

            return redirect()->route('admin.office-assignments.index')
                ->with('success', 'Office assignment deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete office assignment', [
                'error' => $e->getMessage(),
                'assignment_id' => $officeAssignment->id,
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete office assignment. Please try again.');
        }
    }

    /**
     * Toggle the active status of an office assignment.
     */
    public function toggleStatus(OfficeAssignment $officeAssignment): RedirectResponse
    {
        try {
            DB::transaction(function () use ($officeAssignment) {
                $oldStatus = $officeAssignment->is_active;
                $newStatus = !$oldStatus;

                $officeAssignment->update([
                    'is_active' => $newStatus,
                    'updated_by' => auth()->id(),
                ]);

                // Log the status change
                $this->auditTrailService->logOfficeAssignment(
                    'office_assignment_status_toggled',
                    $officeAssignment,
                    $officeAssignment->office,
                    $officeAssignment->user->employee,
                    [
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                    ]
                );

                // Log user activity
                Log::info('Office assignment status toggled', [
                    'assignment_id' => $officeAssignment->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'toggled_by' => auth()->id(),
                    'timestamp' => now()->toDateTimeString(),
                ]);
            });

            $status = $officeAssignment->is_active ? 'activated' : 'deactivated';
            return redirect()->back()
                ->with('success', "Office assignment {$status} successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to toggle office assignment status', [
                'error' => $e->getMessage(),
                'assignment_id' => $officeAssignment->id,
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to toggle assignment status. Please try again.');
        }
    }

    /**
     * Bulk create office assignments.
     */
    public function bulkCreate(): View
    {
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $users = User::with(['roles', 'employee'])
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'HR Admin', 'Employee']);
            })
            ->orderBy('email')
            ->get();

        return view('admin.office-assignments.bulk-create', compact(
            'offices',
            'users'
        ));
    }

    /**
     * Store bulk office assignments.
     */
    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.office_id' => 'required|exists:offices,id',
            'assignments.*.role' => 'required|in:Department Head,Assessor,Final Approver',
        ], [
            'assignments.required' => 'Please provide at least one assignment',
            'assignments.*.user_id.required' => 'User is required for all assignments',
            'assignments.*.office_id.required' => 'Office is required for all assignments',
            'assignments.*.role.required' => 'Role is required for all assignments',
        ]);

        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        try {
            DB::transaction(function () use ($validated, &$successCount, &$failureCount, &$errors) {
                foreach ($validated['assignments'] as $index => $assignmentData) {
                    try {
                        // Check if assignment already exists
                        $existingAssignment = OfficeAssignment::where('user_id', $assignmentData['user_id'])
                            ->where('office_id', $assignmentData['office_id'])
                            ->where('role', $assignmentData['role'])
                            ->first();

                        if ($existingAssignment) {
                            $failureCount++;
                            $errors[$index] = 'Assignment already exists';
                            continue;
                        }

                        OfficeAssignment::create([
                            'user_id' => $assignmentData['user_id'],
                            'office_id' => $assignmentData['office_id'],
                            'role' => $assignmentData['role'],
                            'is_active' => true,
                            'assigned_by' => auth()->id(),
                            'assigned_at' => now(),
                        ]);

                        $successCount++;

                    } catch (\Exception $e) {
                        $failureCount++;
                        $errors[$index] = $e->getMessage();
                    }
                }

                // Log bulk assignment creation
                $this->auditTrailService->logBulkActivity(
                    'office_assignments_bulk_created',
                    [
                        'total_assignments' => count($validated['assignments']),
                        'success_count' => $successCount,
                        'failure_count' => $failureCount,
                    ]
                );
            });

            if ($failureCount > 0) {
                return redirect()->back()
                    ->with('warning', "{$successCount} assignments created successfully. {$failureCount} assignments failed.")
                    ->with('errors', $errors);
            }

            return redirect()->route('admin.office-assignments.index')
                ->with('success', "{$successCount} office assignments created successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to create bulk office assignments', [
                'error' => $e->getMessage(),
                'validated_data' => $validated,
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create bulk office assignments. Please try again.');
        }
    }

    // OPCR-specific methods
    /**
     * Display a listing of office assignments for a specific office for OPCR.
     */
    public function opcrIndex(Request $request, Office $office): View
    {
        $query = OfficeAssignment::with(['user', 'employee', 'assignedBy'])
            ->where('office_id', $office->id)
            ->orderBy('role')
            ->orderBy('created_at', 'desc');

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($subQuery) use ($search) {
                    $subQuery->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('employee_number', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('user', function ($subQuery) use ($search) {
                    $subQuery->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $assignments = $query->paginate(20);
        $activeAssignments = $office->assignments()->where('is_active', true)->count();

        return view('opcr.offices.assignments.index', compact(
            'office',
            'assignments',
            'activeAssignments'
        ));
    }

    /**
     * Show the form for creating a new office assignment for OPCR.
     */
    public function opcrCreate(Office $office): View
    {
        $employees = Employee::whereNull('deleted_at')
            ->with(['user'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $roles = [
            'Department Head' => 'Department Head',
            'Assessor' => 'Assessor (PMT)',
            'Final Approver' => 'Final Approver (Mayor)',
            'Staff' => 'Staff',
            'Supervisor' => 'Supervisor',
            'Member' => 'Member',
        ];

        return view('opcr.offices.assignments.create', compact(
            'office',
            'employees',
            'roles'
        ));
    }

    /**
     * Store a newly created office assignment in storage for OPCR.
     */
    public function opcrStore(Request $request, Office $office): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|in:Department Head,Assessor,Final Approver,Staff,Supervisor,Member',
            'assigned_date' => 'required|date|before_or_equal:today',
            'ended_date' => 'nullable|date|after:assigned_date',
            'is_active' => 'boolean',
            'remarks' => 'nullable|string|max:1000',
        ], [
            'employee_id.required' => 'Please select an employee',
            'role.required' => 'Please select a role',
            'role.in' => 'Invalid role selected',
            'assigned_date.required' => 'Assignment date is required',
            'assigned_date.before_or_equal' => 'Assignment date cannot be in the future',
            'ended_date.after' => 'End date must be after assignment date',
        ]);

        // Get the employee and their user
        $employee = Employee::findOrFail($validated['employee_id']);
        $user = $employee->user;

        if (!$user) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Selected employee does not have an associated user account.');
        }

        // Check if assignment already exists
        $existingAssignment = OfficeAssignment::where('employee_id', $validated['employee_id'])
            ->where('office_id', $office->id)
            ->where('role', $validated['role'])
            ->where('is_active', true)
            ->first();

        if ($existingAssignment) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This employee is already assigned to this office with the same role.');
        }

        // If assigning as Department Head or Assessor, deactivate existing assignments for this office
        if (in_array($validated['role'], ['Department Head', 'Assessor'])) {
            $existingAssignments = OfficeAssignment::where('office_id', $office->id)
                ->where('role', $validated['role'])
                ->where('is_active', true)
                ->get();

            foreach ($existingAssignments as $existingAssignment) {
                $existingAssignment->update([
                    'is_active' => false,
                    'ended_date' => now(),
                ]);

                // Log the deactivation
                $this->auditTrailService->logOfficeAssignment(
                    'office_assignment_deactivated',
                    $existingAssignment,
                    $existingAssignment->office,
                    [
                        'new_role' => $validated['role'],
                        'deactivation_reason' => 'New ' . $validated['role'] . ' assigned to office',
                        'deactivated_by' => Auth::id(),
                    ]
                );
            }
        }

        try {
            DB::transaction(function () use ($validated, $office, $employee, $user) {
                $assignment = OfficeAssignment::create([
                    'employee_id' => $validated['employee_id'],
                    'user_id' => $user->id,
                    'office_id' => $office->id,
                    'role' => $validated['role'],
                    'assigned_date' => $validated['assigned_date'],
                    'ended_date' => $validated['ended_date'] ?? null,
                    'is_active' => $validated['is_active'] ?? true,
                    'remarks' => $validated['remarks'] ?? null,
                    'assigned_by' => Auth::id(),
                ]);

                // Log the assignment creation
                $this->auditTrailService->logOfficeAssignment(
                    'office_assignment_created',
                    $assignment,
                    $office,
                    $employee
                );
            });

            return redirect()
                ->route('opcr.offices.assignments.index', $office)
                ->with('success', 'Office assignment created successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to create OPCR office assignment', [
                'error' => $e->getMessage(),
                'validated_data' => $validated,
                'user_id' => Auth::id(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create office assignment. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified office assignment for OPCR.
     */
    public function opcrEdit(Office $office, OfficeAssignment $assignment): View
    {
        $assignment->load(['user', 'office', 'user.employee']);

        $employees = Employee::with(['user'])
            ->where('deleted_at', null)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $roles = [
            'Department Head' => 'Department Head - Manages office OPCR creation and updates',
            'Assessor (PMT)' => 'Assessor (PMT) - Evaluates OPCR performance',
            'Final Approver (Mayor)' => 'Final Approver (Mayor) - Gives final approval',
            'Staff' => 'Staff',
            'Supervisor' => 'Supervisor',
            'Member' => 'Member',
        ];

        return view('opcr.offices.assignments.edit', compact(
            'office',
            'assignment',
            'employees',
            'roles'
        ));
    }

    /**
     * Update the specified office assignment in storage for OPCR.
     */
    public function opcrUpdate(Request $request, Office $office, OfficeAssignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|in:Department Head,Assessor,Final Approver,Staff,Supervisor,Member',
            'assigned_date' => 'required|date',
            'ended_date' => 'nullable|date|after_or_equal:assigned_date',
            'is_active' => 'boolean',
            'remarks' => 'nullable|string|max:1000',
        ], [
            'employee_id.required' => 'Please select an employee',
            'role.required' => 'Please select a role',
            'role.in' => 'Invalid role selected',
            'assigned_date.required' => 'Please select an assignment date',
            'ended_date.after_or_equal' => 'End date must be after or equal to assignment date',
        ]);

        // Get the employee and user for the assignment
        $employee = Employee::findOrFail($validated['employee_id']);
        $user = $employee->user;

        if (!$user) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Selected employee does not have a user account.');
        }

        // Check if assignment already exists for this employee/office/role combination
        $existingAssignment = OfficeAssignment::where('employee_id', $validated['employee_id'])
            ->where('office_id', $office->id)
            ->where('role', $validated['role'])
            ->where('id', '!=', $assignment->id)
            ->first();

        if ($existingAssignment) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This employee is already assigned to this office with the same role.');
        }

        // If assigning as Department Head or Assessor, deactivate existing assignments for this office
        if (in_array($validated['role'], ['Department Head', 'Assessor'])) {
            $existingAssignments = OfficeAssignment::where('office_id', $office->id)
                ->where('role', $validated['role'])
                ->where('id', '!=', $assignment->id)
                ->where('is_active', true)
                ->get();

            foreach ($existingAssignments as $existingAssignment) {
                $existingAssignment->update([
                    'is_active' => false,
                    'ended_date' => now(),
                ]);

                // Log the deactivation
                $this->auditTrailService->logOfficeAssignment(
                    'office_assignment_deactivated',
                    $existingAssignment,
                    $existingAssignment->office,
                    [
                        'new_role' => $validated['role'],
                        'deactivation_reason' => 'Updated assignment to ' . $validated['role'],
                        'deactivated_by' => Auth::id(),
                    ]
                );
            }
        }

        try {
            DB::transaction(function () use ($validated, $office, $employee, $user, $assignment) {
                $oldData = $assignment->toArray();

                $assignment->update([
                    'employee_id' => $validated['employee_id'],
                    'user_id' => $user->id,
                    'office_id' => $office->id,
                    'role' => $validated['role'],
                    'assigned_date' => $validated['assigned_date'],
                    'ended_date' => $validated['ended_date'] ?? null,
                    'is_active' => $validated['is_active'] ?? true,
                    'remarks' => $validated['remarks'] ?? null,
                    'assigned_by' => Auth::id(),
                ]);

                // Log the assignment update
                $this->auditTrailService->logOfficeAssignment(
                    'office_assignment_updated',
                    $assignment,
                    $office,
                    $employee,
                    [
                        'old_data' => $oldData,
                        'new_data' => $validated,
                    ]
                );
            });

            return redirect()
                ->route('opcr.offices.assignments.index', $office)
                ->with('success', 'Office assignment updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update OPCR office assignment', [
                'error' => $e->getMessage(),
                'validated_data' => $validated,
                'assignment_id' => $assignment->id,
                'user_id' => Auth::id(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update office assignment. Please try again.');
        }
    }
}