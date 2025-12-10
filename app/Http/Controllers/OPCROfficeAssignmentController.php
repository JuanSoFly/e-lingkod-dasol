<?php

namespace App\Http\Controllers;

use App\Models\OfficeAssignment;
use App\Models\Office;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\EmployeeDepartmentSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class OPCROfficeAssignmentController extends Controller
{
    private AuditTrailService $auditTrailService;
    private EmployeeDepartmentSyncService $departmentSyncService;

    public function __construct(AuditTrailService $auditTrailService, EmployeeDepartmentSyncService $departmentSyncService)
    {
        $this->middleware(['auth', 'permission:opcr.view'])->only(['index', 'show']);
        $this->middleware('permission:opcr.create')->only(['create', 'store']);
        $this->middleware('permission:opcr.edit')->only(['edit', 'update']);
        $this->middleware('permission:opcr.delete')->only(['destroy']);
        $this->auditTrailService = $auditTrailService;
        $this->departmentSyncService = $departmentSyncService;
    }

    /**
     * Display a listing of office assignments for a specific office.
     */
    public function index(Request $request, Office $office): View
    {
        // Base query for all assignments with search and role filters
        $query = OfficeAssignment::with(['user.employee', 'assignedBy'])
            ->where('office_id', $office->id)
            ->withoutArchivedPersonnel()
            ->distinct('office_assignments.id');

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

        // Get filtered assignments and separate by status
        $allAssignments = $query->orderBy('is_active', 'desc')
            ->orderBy('role')
            ->orderBy('created_at', 'desc')
            ->get();

        // Separate active and inactive assignments
        $activeAssignments = $allAssignments->where('is_active', true);
        $inactiveAssignments = $allAssignments->where('is_active', false);

        // Get counts for display
        $activeCount = $activeAssignments->count();
        $inactiveCount = $inactiveAssignments->count();

        return view('opcr.offices.assignments.index', compact(
            'office',
            'activeAssignments',
            'inactiveAssignments',
            'activeCount',
            'inactiveCount'
        ));
    }

    /**
     * Show the form for creating a new office assignment.
     */
    public function create(Office $office): View
    {
        // Debug: Log what office we're working with
        \Log::info('OPCR Assignment Create - Office: ' . $office->name . ' (ID: ' . $office->id . ')');

        // Get employees assigned to this office for assignment creation
        // Include both direct office_id assignments and active office assignments
        $employees = Employee::where(function($query) use ($office) {
                $query->where('office_id', $office->id)
                      ->orWhereHas('activeOfficeAssignments', function($subQuery) use ($office) {
                          $subQuery->where('office_id', $office->id);
                      });
            })
            ->whereNull('deleted_at')
            ->with(['user'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Debug: Log how many employees we found
        \Log::info('OPCR Assignment Create - Found ' . $employees->count() . ' employees for office ' . $office->name);

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
     * Store a newly created office assignment in storage.
     */
    public function store(Request $request, Office $office): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                'exists:employees,id',
                function ($attribute, $value, $fail) use ($office) {
                    $employee = Employee::find($value);
                    if (!$employee || !$this->employeeBelongsToOffice($employee, $office)) {
                        $fail('The selected employee must belong to this office.');
                    }
                },
            ],
            'role' => 'required|in:Department Head,Assessor,Final Approver,Staff,Supervisor,Member',
            'assigned_date' => 'required|date|before_or_equal:today',
            'ended_date' => 'nullable|date|after:assigned_date',
            'is_active' => 'boolean',
            'remarks' => 'nullable|string|max:1000',
        ], [
            'employee_id.required' => 'Please select an employee',
            'employee_id.exists' => 'The selected employee is invalid',
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

        // Check if employee already has any active assignment in this office
        $existingActiveAssignment = OfficeAssignment::where('employee_id', $validated['employee_id'])
            ->where('office_id', $office->id)
            ->where('is_active', true)
            ->first();

        if ($existingActiveAssignment) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This employee already has an active assignment in this office. Please end the existing assignment first or assign them to a different office.');
        }

        // Check if user already has any active assignment in this office (prevents duplicates)
        $existingUserAssignment = OfficeAssignment::where('user_id', $user->id)
            ->where('office_id', $office->id)
            ->where('is_active', true)
            ->where('id', '!=', $existingActiveAssignment->id ?? null)
            ->first();

        if ($existingUserAssignment) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This user already has an active assignment in this office. Each user can only have one active assignment per office.');
        }

        $isActiveAssignment = $validated['is_active'] ?? true;
        $shouldAssignDepartmentHead = $validated['role'] === OfficeAssignment::ROLE_DEPARTMENT_HEAD && $isActiveAssignment;

        // If assigning as Department Head, deactivate existing department heads for this office
        if ($shouldAssignDepartmentHead) {
            $this->demoteOldDepartmentHeads($office, $user);
        }

        try {
            DB::transaction(function () use ($validated, $office, $employee, $user, $shouldAssignDepartmentHead) {
                if ($shouldAssignDepartmentHead) {
                    $this->persistOfficeDepartmentHead($office, $employee->id);
                }

                $assignment = OfficeAssignment::create([
                    'employee_id' => $validated['employee_id'],
                    'user_id' => $user->id,
                    'office_id' => $office->id,
                    'role' => $validated['role'],
                    'assigned_date' => $validated['assigned_date'],
                    'ended_date' => empty($validated['ended_date']) ? null : $validated['ended_date'],
                    'is_active' => $validated['is_active'] ?? true,
                    'remarks' => $validated['remarks'] ?? null,
                    'assigned_by' => Auth::id(),
                ]);

                if ($shouldAssignDepartmentHead) {
                    $office->refresh();
                    $this->syncOfficeDepartmentHead($office, $assignment->id);
                } else {
                    $this->syncOfficeDepartmentHead($office);
                }

                // Sync department information
                $this->departmentSyncService->syncOnAssignmentCreate($assignment);

                // Sync User roles based on assignment
                $this->syncUserRoles($user, $validated['role'], true);

                // Log the assignment creation
                $this->auditTrailService->logOPCRActivity(
                    'office_assignment_created',
                    null,
                    [
                        'assignment_id' => $assignment->id,
                        'employee_id' => $assignment->employee_id,
                        'employee_name' => $employee->full_name,
                        'user_id' => $assignment->user_id,
                        'office_id' => $office->id,
                        'office_name' => $office->name,
                        'role' => $assignment->role,
                        'assigned_by' => Auth::id(),
                    ]
                );

                // Log user activity
                Log::info('OPCR office assignment created', [
                    'assignment_id' => $assignment->id,
                    'employee_id' => $assignment->employee_id,
                    'office_id' => $office->id,
                    'role' => $assignment->role,
                    'created_by' => Auth::id(),
                    'timestamp' => now()->toDateTimeString(),
                ]);
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
     * Display the specified office assignment.
     */
    public function show(Office $office, OfficeAssignment $assignment): View
    {
        if ($assignment->office_id !== $office->id) {
            abort(404);
        }

        $assignment->load(['user', 'employee', 'office', 'assignedBy']);

        return view('opcr.offices.assignments.show', compact('office', 'assignment'));
    }

    /**
     * Show the form for editing the specified office assignment.
     */
    public function edit(Office $office, OfficeAssignment $assignment): View
    {
        if ($assignment->office_id !== $office->id) {
            abort(404);
        }

        $assignment->load(['user', 'employee']);

        // Get employees assigned to this office for assignment editing
        // Include both direct office_id assignments and active office assignments
        $employees = Employee::where(function($query) use ($office) {
                $query->where('office_id', $office->id)
                      ->orWhereHas('activeOfficeAssignments', function($subQuery) use ($office) {
                          $subQuery->where('office_id', $office->id);
                      });
            })
            ->whereNull('deleted_at')
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

        return view('opcr.offices.assignments.edit', compact(
            'office',
            'assignment',
            'employees',
            'roles'
        ));
    }

    /**
     * Update the specified office assignment in storage.
     */
    public function update(Request $request, Office $office, OfficeAssignment $assignment): RedirectResponse
    {
        if ($assignment->office_id !== $office->id) {
            abort(404);
        }

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'exists:employees,id',
                function ($attribute, $value, $fail) use ($office) {
                    $employee = Employee::find($value);
                    if (!$employee || !$this->employeeBelongsToOffice($employee, $office)) {
                        $fail('The selected employee must belong to this office.');
                    }
                },
            ],
            'role' => 'required|in:Department Head,Assessor,Final Approver,Staff,Supervisor,Member',
            'assigned_date' => 'required|date|before_or_equal:today',
            'ended_date' => 'nullable|date|after:assigned_date',
            'is_active' => 'boolean',
            'remarks' => 'nullable|string|max:1000',
        ]);

        // Get the employee and their user
        $employee = Employee::findOrFail($validated['employee_id']);
        $user = $employee->user;

        if (!$user) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Selected employee does not have an associated user account.');
        }

        // Check if employee already has any other active assignment in this office (excluding current assignment)
        $existingActiveAssignment = OfficeAssignment::where('employee_id', $validated['employee_id'])
            ->where('office_id', $office->id)
            ->where('id', '!=', $assignment->id)
            ->where('is_active', true)
            ->first();

        if ($existingActiveAssignment) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This employee already has another active assignment in this office. Please end the existing assignment first.');
        }

        // Check if user already has any other active assignment in this office (excluding current assignment)
        $existingUserAssignment = OfficeAssignment::where('user_id', $user->id)
            ->where('office_id', $office->id)
            ->where('id', '!=', $assignment->id)
            ->where('is_active', true)
            ->first();

        if ($existingUserAssignment) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This user already has another active assignment in this office. Each user can only have one active assignment per office.');
        }

        $isActiveAssignment = $validated['is_active'] ?? true;
        $shouldBecomeDepartmentHead = $validated['role'] === OfficeAssignment::ROLE_DEPARTMENT_HEAD && $isActiveAssignment;
        $shouldClearDepartmentHead = !$shouldBecomeDepartmentHead
            && $assignment->role === OfficeAssignment::ROLE_DEPARTMENT_HEAD
            && $office->department_head_id === $assignment->employee_id;

        try {
            DB::transaction(function () use ($validated, $office, $assignment, $employee, $user, $shouldBecomeDepartmentHead, $shouldClearDepartmentHead) {
                if ($shouldBecomeDepartmentHead && $assignment->role !== OfficeAssignment::ROLE_DEPARTMENT_HEAD) {
                    $this->demoteOldDepartmentHeads($office, $user, $assignment->id);
                }

                if ($shouldBecomeDepartmentHead) {
                    $this->persistOfficeDepartmentHead($office, $employee->id);
                } elseif ($shouldClearDepartmentHead) {
                    $this->persistOfficeDepartmentHead($office, null);
                }

                $assignment->update([
                    'employee_id' => $validated['employee_id'],
                    'user_id' => $user->id,
                    'role' => $validated['role'],
                    'assigned_date' => $validated['assigned_date'],
                    'ended_date' => empty($validated['ended_date']) ? null : $validated['ended_date'],
                    'is_active' => $validated['is_active'] ?? true,
                    'remarks' => $validated['remarks'] ?? null,
                ]);

                if ($shouldBecomeDepartmentHead || $shouldClearDepartmentHead) {
                    $office->refresh();
                    $this->syncOfficeDepartmentHead($office, $shouldBecomeDepartmentHead ? $assignment->id : null);
                }

                // Sync department information
                $this->departmentSyncService->syncOnAssignmentUpdate($assignment);

                // Sync User roles based on assignment change
                $this->syncUserRoles($user, $validated['role'], $validated['is_active'] ?? true);

                // Log the assignment update
                $this->auditTrailService->logOfficeAssignment(
                    'office_assignment_updated',
                    $assignment,
                    $office,
                    $employee,
                    [
                        'role' => $assignment->role,
                        'updated_by' => Auth::id(),
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
                'user_id' => Auth::id(),
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
    public function destroy(Office $office, OfficeAssignment $assignment): RedirectResponse
    {
        if ($assignment->office_id !== $office->id) {
            abort(404);
        }

        $shouldClearDepartmentHead = $assignment->role === OfficeAssignment::ROLE_DEPARTMENT_HEAD
            && $office->department_head_id === $assignment->employee_id;

        try {
            DB::transaction(function () use ($assignment, $office, $shouldClearDepartmentHead) {
                if ($shouldClearDepartmentHead) {
                    $this->persistOfficeDepartmentHead($office, null);
                }

                // Soft delete the assignment
                $assignment->delete();

                $office->refresh();
                $this->syncOfficeDepartmentHead($office);

                // Sync department information after deletion
                $this->departmentSyncService->syncOnAssignmentDelete($assignment);

                // Log the assignment deletion
                $this->auditTrailService->logOPCRActivity(
                    'office_assignment_deleted',
                    null,
                    [
                        'assignment_id' => $assignment->id,
                        'employee_id' => $assignment->employee_id,
                        'employee_name' => $assignment->employee->full_name ?? 'Unknown',
                        'user_id' => $assignment->user_id,
                        'office_id' => $office->id,
                        'office_name' => $office->name,
                        'role' => $assignment->role,
                        'deleted_by' => Auth::id(),
                    ]
                );
            });

            return redirect()
                ->route('opcr.offices.assignments.index', $office)
                ->with('success', 'Office assignment deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete OPCR office assignment', [
                'error' => $e->getMessage(),
                'assignment_id' => $assignment->id,
                'user_id' => Auth::id(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete office assignment. Please try again.');
        }
    }

    /**
     * Keep office_assignments synchronized with the office.department_head_id (authoritative source).
     */
    private function syncOfficeDepartmentHead(Office $office, ?int $excludeAssignmentId = null): void
    {
        // Use the legacy department_head_id as the authoritative source
        $departmentHeadId = $office->department_head_id;

        if (!$departmentHeadId) {
            // No department head assigned, deactivate all department head assignments
            $query = OfficeAssignment::where('office_id', $office->id)
                ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
                ->where('is_active', true);

            // Exclude current assignment if specified
            if ($excludeAssignmentId) {
                $query->where('id', '!=', $excludeAssignmentId);
            }

            $query->update([
                'is_active' => false,
                'ended_date' => now(),
            ]);
            return;
        }

        // Get the employee and user for the department head
        $departmentHead = Employee::find($departmentHeadId);
        if (!$departmentHead || !$departmentHead->user) {
            return;
        }

        // Check if user already has an active Department Head assignment in this office
        $existingActiveAssignment = OfficeAssignment::where('office_id', $office->id)
            ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
            ->where('user_id', $departmentHead->user->id)
            ->where('is_active', true)
            ->first();

        if ($existingActiveAssignment) {
            Log::info('syncOfficeDepartmentHead - user already has active Department Head assignment', [
                'user_id' => $departmentHead->user->id,
                'assignment_id' => $existingActiveAssignment->id
            ]);
            return; // User already has active assignment, nothing to do
        }

        // Deactivate existing department head assignments for other users
        $query = OfficeAssignment::where('office_id', $office->id)
            ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
            ->where('is_active', true)
            ->where('user_id', '!=', $departmentHead->user->id);

        // Exclude current assignment if specified
        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        $query->update([
            'is_active' => false,
            'ended_date' => now(),
        ]);

        // Check if there's already an active assignment for this user (excluding the current assignment)
        $query = OfficeAssignment::where('office_id', $office->id)
            ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
            ->where('user_id', $departmentHead->user->id)
            ->where('is_active', true);

        // Exclude current assignment if specified
        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        // Debug: Check what active assignments exist for this user
        $allActiveAssignments = OfficeAssignment::where('office_id', $office->id)
            ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
            ->where('user_id', $departmentHead->user->id)
            ->where('is_active', true)
            ->get();

        Log::info('syncOfficeDepartmentHead debug - all active assignments for user', [
            'user_id' => $departmentHead->user->id,
            'assignments_found' => $allActiveAssignments->count(),
            'assignments' => $allActiveAssignments->pluck('id')->toArray(),
            'excluding_assignment_id' => $excludeAssignmentId
        ]);

        $existingAssignment = $query->first();

        Log::info('syncOfficeDepartmentHead assignment check', [
            'office_id' => $office->id,
            'department_head_id' => $departmentHeadId,
            'user_id' => $departmentHead->user->id,
            'exclude_assignment_id' => $excludeAssignmentId,
            'found_existing_assignment' => $existingAssignment ? $existingAssignment->id : null,
            'existing_is_active' => $existingAssignment ? $existingAssignment->is_active : null
        ]);

        if (!$existingAssignment) {
            // Create new assignment if none exists
            Log::info('syncOfficeDepartmentHead creating new assignment', [
                'office_id' => $office->id,
                'user_id' => $departmentHead->user->id,
                'employee_id' => $departmentHeadId
            ]);
            // Check if there's any existing assignment (active or inactive) for this user, office, and role
            $anyExistingAssignment = OfficeAssignment::where('office_id', $office->id)
                ->where('user_id', $departmentHead->user->id)
                ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
                ->first();

            if ($anyExistingAssignment) {
                // If assignment exists but is inactive, reactivate it
                if (!$anyExistingAssignment->is_active) {
                    $anyExistingAssignment->update([
                        'employee_id' => $departmentHeadId,
                        'is_active' => true,
                        'ended_date' => null,
                        'assigned_date' => now(),
                    ]);
                    Log::info('syncOfficeDepartmentHead reactivated existing assignment', [
                        'assignment_id' => $anyExistingAssignment->id
                    ]);
                }
            } else {
                // Create new assignment if none exists
                OfficeAssignment::create([
                    'office_id' => $office->id,
                    'user_id' => $departmentHead->user->id,
                    'role' => OfficeAssignment::ROLE_DEPARTMENT_HEAD,
                    'employee_id' => $departmentHeadId,
                    'assigned_date' => now(),
                    'is_active' => true,
                    'ended_date' => null,
                    'assigned_by' => Auth::id(),
                ]);
                Log::info('syncOfficeDepartmentHead created new assignment', [
                    'office_id' => $office->id,
                    'user_id' => $departmentHead->user->id
                ]);
            }
        } else {
            // Update existing assignment to ensure it has correct employee_id
            $existingAssignment->update([
                'employee_id' => $departmentHeadId,
                'is_active' => true,
                'ended_date' => null,
            ]);
        }
    }

    /**
     * Check if an employee belongs to an office based on active office assignments
     */
    private function employeeBelongsToOffice($employee, $office): bool
    {
        // Primary validation: Check if employee is assigned to this office via office_id
        if ($employee->office_id === $office->id) {
            return true;
        }

        // Secondary validation: Check if employee has active office assignments for this office
        return $employee->activeOfficeAssignments()
            ->where('office_id', $office->id)
            ->exists();
    }

    /**
     * Demote old department heads when a new one is assigned
     */
    private function demoteOldDepartmentHeads(Office $office, User $newDepartmentHead, ?int $excludeAssignmentId = null): void
    {
        Log::info('demoteOldDepartmentHeads called', [
            'office_id' => $office->id,
            'exclude_assignment_id' => $excludeAssignmentId,
            'user_id' => $newDepartmentHead->id
        ]);

        // Get current active department head assignments for this office
        $query = OfficeAssignment::where('office_id', $office->id)
            ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
            ->where('is_active', true);

        // Exclude the current assignment if updating
        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        $oldAssignments = $query->get();

        // Also check for existing Department Head assignments for the same user (regardless of active status)
        // This handles cases where an employee already has a Department Head assignment but it's inactive
        $userDeptHeadQuery = OfficeAssignment::where('office_id', $office->id)
            ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
            ->where('user_id', $newDepartmentHead->id);

        // Exclude the current assignment if updating
        if ($excludeAssignmentId) {
            $userDeptHeadQuery->where('id', '!=', $excludeAssignmentId);
        }

        $userDeptHeadAssignments = $userDeptHeadQuery->get();

        // Merge the results, prioritizing user-specific assignments
        $allAssignments = $userDeptHeadAssignments->merge($oldAssignments)->unique('id');

        Log::info('Found old department head assignments', [
            'count' => $allAssignments->count(),
            'assignments' => $allAssignments->pluck('id')->toArray(),
            'user_specific_count' => $userDeptHeadAssignments->count(),
            'user_specific_assignments' => $userDeptHeadAssignments->pluck('id')->toArray()
        ]);

        foreach ($allAssignments as $oldAssignment) {
            // Deactivate the old assignment
            $oldAssignment->update([
                'is_active' => false,
                'ended_date' => now(),
            ]);

            // Update the old department head's user record
            $oldUser = $oldAssignment->user;
            if ($oldUser) {
                $this->syncUserRoles($oldUser, OfficeAssignment::ROLE_DEPARTMENT_HEAD, false);

                Log::info('Demoted old department head', [
                    'old_user_id' => $oldUser->id,
                    'old_user_email' => $oldUser->email,
                    'office_id' => $office->id,
                    'office_name' => $office->name,
                    'new_user_id' => $newDepartmentHead->id,
                    'new_user_email' => $newDepartmentHead->email,
                    'demoted_by' => Auth::id(),
                ]);
            }
        }
    }

    /**
     * Persist the authoritative Department Head for an office.
     */
    private function persistOfficeDepartmentHead(Office $office, ?int $employeeId): void
    {
        if ($office->department_head_id === $employeeId) {
            return;
        }

        $office->department_head_id = $employeeId;
        $office->save();
    }

    /**
     * Sync User roles based on office assignment changes
     */
    private function syncUserRoles(User $user, string $role, bool $isActive): void
    {
        // Get employee data for user table updates
        $employee = $user->employee;

        // Update user table fields based on assignment
        if ($employee) {
            // Get current active assignments to determine role and department
            $activeAssignments = OfficeAssignment::where('user_id', $user->id)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('ended_date')
                          ->orWhere('ended_date', '>=', now());
                })
                ->with('office')
                ->get();

            $updateData = [
                'position' => $employee->position,
                'is_department_head' => false,
            ];

            // Set department based on the most recent active assignment
            if ($activeAssignments->isNotEmpty()) {
                $mostRecentAssignment = $activeAssignments->sortByDesc('assigned_date')->first();
                if ($mostRecentAssignment && $mostRecentAssignment->office) {
                    $updateData['department'] = $mostRecentAssignment->office->name;
                }
            }

            // Determine primary role and department head status
            $isDepartmentHead = $activeAssignments->contains('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD);
            $updateData['is_department_head'] = $isDepartmentHead;

            // Set office_role based on highest priority assignment
            if ($isDepartmentHead) {
                $updateData['office_role'] = 'Department Head';
            } elseif ($activeAssignments->contains('role', OfficeAssignment::ROLE_ASSESSOR)) {
                $updateData['office_role'] = 'Assessor';
            } elseif ($activeAssignments->contains('role', OfficeAssignment::ROLE_FINAL_APPROVER)) {
                $updateData['office_role'] = 'Final Approver';
            } elseif ($activeAssignments->contains('role', OfficeAssignment::ROLE_SUPERVISOR)) {
                $updateData['office_role'] = 'Supervisor';
            } else {
                $updateData['office_role'] = 'Member';
            }

            $user->update($updateData);

            Log::info('Updated user table fields', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'position' => $updateData['position'],
                'department' => $updateData['department'],
                'office_role' => $updateData['office_role'],
                'is_department_head' => $updateData['is_department_head'],
                'updated_by' => Auth::id(),
            ]);
        }

        // Only sync Department Head role to User permissions
        if ($role === OfficeAssignment::ROLE_DEPARTMENT_HEAD) {
            if ($isActive) {
                // Add Department Head role if they don't have it
                if (!$user->hasRole('Department Head')) {
                    $user->assignRole('Department Head');
                    Log::info('Assigned Department Head role to user', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'assigned_by' => Auth::id(),
                    ]);
                }

                // Remove Employee role if they have it (to avoid conflicts)
                if ($user->hasRole('Employee')) {
                    $user->removeRole('Employee');
                    Log::info('Removed Employee role from Department Head', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'removed_by' => Auth::id(),
                    ]);
                }
            } else {
                // Remove Department Head role if assignment is deactivated
                if ($user->hasRole('Department Head')) {
                    $user->removeRole('Department Head');
                    Log::info('Removed Department Head role from user', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'removed_by' => Auth::id(),
                    ]);

                    // Add Employee role back if they don't have any other special roles
                    if (!$user->hasAnyRole(['HR Admin', 'Super Admin', 'Assessor', 'Final Approver'])) {
                        $user->assignRole('Employee');
                        Log::info('Assigned Employee role back to user', [
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'assigned_by' => Auth::id(),
                        ]);
                    }
                }
            }
        }

        // Handle other role assignments as needed
        switch ($role) {
            case OfficeAssignment::ROLE_ASSESSOR:
                if ($isActive && !$user->hasRole('Assessor')) {
                    $user->assignRole('Assessor');
                } elseif (!$isActive && $user->hasRole('Assessor')) {
                    $user->removeRole('Assessor');
                }
                break;

            case OfficeAssignment::ROLE_FINAL_APPROVER:
                if ($isActive && !$user->hasRole('Final Approver')) {
                    $user->assignRole('Final Approver');
                } elseif (!$isActive && $user->hasRole('Final Approver')) {
                    $user->removeRole('Final Approver');
                }
                break;
        }
    }
}
