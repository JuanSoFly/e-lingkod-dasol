<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Office;
use App\Models\OfficeAssignment;
use App\Services\OfficeAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\Activity;
use Spatie\Permission\Models\Role;

class RoleAssignmentController extends Controller
{
    private OfficeAssignmentService $officeAssignmentService;

    public function __construct(OfficeAssignmentService $officeAssignmentService)
    {
        $this->officeAssignmentService = $officeAssignmentService;

        $this->middleware('auth');
        $this->middleware('permission:role.assign')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        $this->middleware('permission:role.view')->only(['show', 'userRoles', 'officeRoles']);
    }

    /**
     * Display a listing of role assignments
     */
    public function index(Request $request): View
    {
        $query = OfficeAssignment::with(['user', 'office', 'user.employee'])
            ->orderBy('office_id')
            ->orderBy('role');

        // Apply filters
        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($subQ) use ($search) {
                      $subQ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $assignments = $query->paginate(20)->withQueryString();

        // Get filter options
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $roles = [
            'Department Head' => 'Department Head',
            'Assessor' => 'Assessor (PMT)',
            'Final Approver' => 'Final Approver (Mayor)',
        ];

        // Get statistics
        $stats = [
            'total_assignments' => OfficeAssignment::count(),
            'active_assignments' => OfficeAssignment::where('is_active', true)->count(),
            'department_heads' => OfficeAssignment::where('role', 'Department Head')->where('is_active', true)->count(),
            'assessors' => OfficeAssignment::where('role', 'Assessor')->where('is_active', true)->count(),
            'final_approvers' => OfficeAssignment::where('role', 'Final Approver')->where('is_active', true)->count(),
        ];

        return view('admin.role-assignments.index', compact(
            'assignments',
            'offices',
            'roles',
            'stats'
        ));
    }

    /**
     * Show the form for creating a new role assignment
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
            'Department Head' => [
                'name' => 'Department Head',
                'description' => 'Manages office OPCR creation and updates',
                'permissions' => ['opcr.create', 'opcr.edit', 'opcr.commit', 'opcr.submit'],
            ],
            'Assessor' => [
                'name' => 'Assessor (PMT)',
                'description' => 'Evaluates OPCR performance',
                'permissions' => ['opcr.view', 'opcr.assess', 'opcr.return'],
            ],
            'Final Approver' => [
                'name' => 'Final Approver (Mayor)',
                'description' => 'Gives final approval to OPCR',
                'permissions' => ['opcr.view', 'opcr.approve', 'opcr.return'],
            ],
        ];

        return view('admin.role-assignments.create', compact(
            'offices',
            'users',
            'roles'
        ));
    }

    /**
     * Store a newly created role assignment
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'office_id' => 'required|exists:offices,id',
            'role' => 'required|in:Department Head,Assessor,Final Approver',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
        ]);

        try {
            // Check if assignment already exists
            $existingAssignment = OfficeAssignment::where('user_id', $request->user_id)
                ->where('office_id', $request->office_id)
                ->where('role', $request->role)
                ->where('is_active', true)
                ->first();

            if ($existingAssignment) {
                return back()
                    ->withInput()
                    ->withErrors(['error' => 'This user is already assigned to this office with the same role.']);
            }

            DB::transaction(function () use ($request) {
                $assignment = OfficeAssignment::create([
                    'user_id' => $request->user_id,
                    'office_id' => $request->office_id,
                    'role' => $request->role,
                    'is_active' => $request->boolean('is_active', true),
                    'notes' => $request->notes,
                    'effective_date' => $request->effective_date ?? now(),
                    'expiry_date' => $request->expiry_date,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                ]);

                // Assign corresponding Laravel permissions
                $this->assignLaravelPermissions($assignment->user, $request->role);

                Activity::log('Role assignment created', [
                    'assignment_id' => $assignment->id,
                    'user_id' => $assignment->user_id,
                    'user_email' => $assignment->user->email,
                    'office_id' => $assignment->office_id,
                    'office_name' => $assignment->office->name,
                    'role' => $assignment->role,
                    'assigned_by' => Auth::id(),
                ]);
            });

            return redirect()
                ->route('role-assignments.index')
                ->with('success', 'Role assignment created successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to create role assignment', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create role assignment: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified role assignment
     */
    public function show(OfficeAssignment $assignment): View
    {
        $assignment->load([
            'user',
            'office',
            'assignedByUser',
            'user.employee'
        ]);

        // Get assignment history
        $history = $this->getAssignmentHistory($assignment);

        return view('admin.role-assignments.show', compact(
            'assignment',
            'history'
        ));
    }

    /**
     * Show the form for editing the specified role assignment
     */
    public function edit(OfficeAssignment $assignment): View
    {
        $assignment->load(['user', 'office']);

        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $users = User::with(['roles', 'employee'])
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'HR Admin', 'Employee']);
            })
            ->orderBy('email')
            ->get();

        $roles = [
            'Department Head' => [
                'name' => 'Department Head',
                'description' => 'Manages office OPCR creation and updates',
                'permissions' => ['opcr.create', 'opcr.edit', 'opcr.commit', 'opcr.submit'],
            ],
            'Assessor' => [
                'name' => 'Assessor (PMT)',
                'description' => 'Evaluates OPCR performance',
                'permissions' => ['opcr.view', 'opcr.assess', 'opcr.return'],
            ],
            'Final Approver' => [
                'name' => 'Final Approver (Mayor)',
                'description' => 'Gives final approval to OPCR',
                'permissions' => ['opcr.view', 'opcr.approve', 'opcr.return'],
            ],
        ];

        return view('admin.role-assignments.edit', compact(
            'assignment',
            'offices',
            'users',
            'roles'
        ));
    }

    /**
     * Update the specified role assignment
     */
    public function update(Request $request, OfficeAssignment $assignment): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'office_id' => 'required|exists:offices,id',
            'role' => 'required|in:Department Head,Assessor,Final Approver',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
        ]);

        try {
            $oldRole = $assignment->role;
            $oldUserId = $assignment->user_id;

            DB::transaction(function () use ($request, $assignment, $oldRole, $oldUserId) {
                $assignment->update([
                    'user_id' => $request->user_id,
                    'office_id' => $request->office_id,
                    'role' => $request->role,
                    'is_active' => $request->boolean('is_active', $assignment->is_active),
                    'notes' => $request->notes ?? $assignment->notes,
                    'effective_date' => $request->effective_date ?? $assignment->effective_date,
                    'expiry_date' => $request->expiry_date,
                    'updated_by' => Auth::id(),
                ]);

                // If role or user changed, update permissions
                if ($oldRole !== $request->role || $oldUserId !== $request->user_id) {
                    // Remove old role permissions
                    $this->removeLaravelPermissions(User::find($oldUserId), $oldRole);

                    // Add new role permissions
                    $this->assignLaravelPermissions($assignment->user, $request->role);
                }

                Activity::log('Role assignment updated', [
                    'assignment_id' => $assignment->id,
                    'old_role' => $oldRole,
                    'new_role' => $request->role,
                    'old_user_id' => $oldUserId,
                    'new_user_id' => $request->user_id,
                    'updated_by' => Auth::id(),
                ]);
            });

            return redirect()
                ->route('role-assignments.show', $assignment)
                ->with('success', 'Role assignment updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update role assignment', [
                'error' => $e->getMessage(),
                'assignment_id' => $assignment->id,
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update role assignment: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified role assignment
     */
    public function destroy(OfficeAssignment $assignment): RedirectResponse
    {
        try {
            DB::transaction(function () use ($assignment) {
                $assignmentData = $assignment->toArray();

                // Remove Laravel permissions
                $this->removeLaravelPermissions($assignment->user, $assignment->role);

                $assignment->delete();

                Activity::log('Role assignment deleted', [
                    'assignment_id' => $assignment->id,
                    'assignment_data' => $assignmentData,
                    'deleted_by' => Auth::id(),
                ]);
            });

            return redirect()
                ->route('role-assignments.index')
                ->with('success', 'Role assignment deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete role assignment', [
                'error' => $e->getMessage(),
                'assignment_id' => $assignment->id,
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withErrors(['error' => 'Failed to delete role assignment: ' . $e->getMessage()]);
        }
    }

    /**
     * Get user's role assignments
     */
    public function userRoles(User $user): JsonResponse
    {
        $assignments = $user->officeAssignments()
            ->with(['office', 'assignedByUser'])
            ->orderBy('assigned_at', 'desc')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'office' => [
                        'id' => $assignment->office->id,
                        'name' => $assignment->office->name,
                        'code' => $assignment->office->code,
                    ],
                    'role' => $assignment->role,
                    'is_active' => $assignment->is_active,
                    'effective_date' => $assignment->effective_date->format('Y-m-d'),
                    'expiry_date' => $assignment->expiry_date?->format('Y-m-d'),
                    'assigned_by' => $assignment->assignedByUser?->name ?? 'System',
                    'assigned_at' => $assignment->assigned_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    /**
     * Get office's role assignments
     */
    public function officeRoles(Office $office): JsonResponse
    {
        $assignments = $office->assignments()
            ->with(['user.employee', 'assignedByUser'])
            ->orderBy('role')
            ->orderBy('assigned_at', 'desc')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'user' => [
                        'id' => $assignment->user->id,
                        'name' => $assignment->user->name,
                        'email' => $assignment->user->email,
                        'employee' => $assignment->user->employee?->full_name,
                    ],
                    'role' => $assignment->role,
                    'is_active' => $assignment->is_active,
                    'effective_date' => $assignment->effective_date->format('Y-m-d'),
                    'expiry_date' => $assignment->expiry_date?->format('Y-m-d'),
                    'assigned_by' => $assignment->assignedByUser?->name ?? 'System',
                    'assigned_at' => $assignment->assigned_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    /**
     * Toggle assignment active status
     */
    public function toggleStatus(OfficeAssignment $assignment): RedirectResponse
    {
        try {
            $newStatus = !$assignment->is_active;

            DB::transaction(function () use ($assignment, $newStatus) {
                $assignment->update([
                    'is_active' => $newStatus,
                    'updated_by' => Auth::id(),
                ]);

                // Update Laravel permissions based on status
                if ($newStatus) {
                    $this->assignLaravelPermissions($assignment->user, $assignment->role);
                } else {
                    $this->removeLaravelPermissions($assignment->user, $assignment->role);
                }

                Activity::log('Role assignment status toggled', [
                    'assignment_id' => $assignment->id,
                    'new_status' => $newStatus,
                    'toggled_by' => Auth::id(),
                ]);
            });

            $statusText = $newStatus ? 'activated' : 'deactivated';
            return back()
                ->with('success', "Role assignment {$statusText} successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to toggle role assignment status', [
                'error' => $e->getMessage(),
                'assignment_id' => $assignment->id,
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withErrors(['error' => 'Failed to toggle assignment status: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk assign roles
     */
    public function bulkAssign(): View
    {
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $users = User::with(['roles', 'employee'])
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'HR Admin', 'Employee']);
            })
            ->orderBy('email')
            ->get();

        $roles = [
            'Department Head' => 'Department Head',
            'Assessor' => 'Assessor (PMT)',
            'Final Approver' => 'Final Approver (Mayor)',
        ];

        return view('admin.role-assignments.bulk-assign', compact(
            'offices',
            'users',
            'roles'
        ));
    }

    /**
     * Store bulk role assignments
     */
    public function bulkStore(Request $request): RedirectResponse
    {
        $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.office_id' => 'required|exists:offices,id',
            'assignments.*.role' => 'required|in:Department Head,Assessor,Final Approver',
        ]);

        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        try {
            DB::transaction(function () use ($request, &$successCount, &$failureCount, &$errors) {
                foreach ($request->assignments as $index => $assignmentData) {
                    try {
                        // Check if assignment already exists
                        $existingAssignment = OfficeAssignment::where('user_id', $assignmentData['user_id'])
                            ->where('office_id', $assignmentData['office_id'])
                            ->where('role', $assignmentData['role'])
                            ->where('is_active', true)
                            ->first();

                        if ($existingAssignment) {
                            $failureCount++;
                            $errors[$index] = 'Assignment already exists';
                            continue;
                        }

                        $assignment = OfficeAssignment::create([
                            'user_id' => $assignmentData['user_id'],
                            'office_id' => $assignmentData['office_id'],
                            'role' => $assignmentData['role'],
                            'is_active' => true,
                            'assigned_by' => Auth::id(),
                            'assigned_at' => now(),
                        ]);

                        // Assign Laravel permissions
                        $this->assignLaravelPermissions($assignment->user, $assignment->role);

                        $successCount++;

                    } catch (\Exception $e) {
                        $failureCount++;
                        $errors[$index] = $e->getMessage();
                    }
                }

                Activity::log('Bulk role assignments created', [
                    'total_assignments' => count($request->assignments),
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'created_by' => Auth::id(),
                ]);
            });

            $message = $successCount > 0
                ? "{$successCount} role assignments created successfully."
                : 'No assignments were created.';

            if ($failureCount > 0) {
                $message .= " {$failureCount} assignments failed.";
                return back()
                    ->with('warning', $message)
                    ->with('errors', $errors);
            }

            return redirect()
                ->route('role-assignments.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to create bulk role assignments', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create bulk role assignments: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign Laravel permissions based on role
     */
    private function assignLaravelPermissions(User $user, string $role): void
    {
        $permissions = match ($role) {
            'Department Head' => ['opcr.create', 'opcr.edit', 'opcr.commit', 'opcr.submit'],
            'Assessor' => ['opcr.view', 'opcr.assess', 'opcr.return'],
            'Final Approver' => ['opcr.view', 'opcr.approve', 'opcr.return'],
            default => [],
        };

        foreach ($permissions as $permission) {
            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }
    }

    /**
     * Remove Laravel permissions based on role
     */
    private function removeLaravelPermissions(User $user, string $role): void
    {
        $permissions = match ($role) {
            'Department Head' => ['opcr.create', 'opcr.edit', 'opcr.commit', 'opcr.submit'],
            'Assessor' => ['opcr.view', 'opcr.assess', 'opcr.return'],
            'Final Approver' => ['opcr.view', 'opcr.approve', 'opcr.return'],
            default => [],
        };

        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                $user->revokePermissionTo($permission);
            }
        }
    }

    /**
     * Get assignment history
     */
    private function getAssignmentHistory(OfficeAssignment $assignment): array
    {
        return DB::table('activity_log')
            ->where('subject_type', OfficeAssignment::class)
            ->where('subject_id', $assignment->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'description' => $log->description,
                    'properties' => json_decode($log->properties, true),
                    'created_at' => $log->created_at,
                    'causer_id' => $log->causer_id,
                ];
            })
            ->toArray();
    }
}