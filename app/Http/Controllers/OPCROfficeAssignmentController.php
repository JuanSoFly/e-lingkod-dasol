<?php

namespace App\Http\Controllers;

use App\Models\OfficeAssignment;
use App\Models\Office;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class OPCROfficeAssignmentController extends Controller
{
    private AuditTrailService $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->middleware(['auth', 'permission:opcr.view'])->only(['index', 'show']);
        $this->middleware('permission:opcr.create')->only(['create', 'store']);
        $this->middleware('permission:opcr.edit')->only(['edit', 'update']);
        $this->middleware('permission:opcr.delete')->only(['destroy']);
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Display a listing of office assignments for a specific office.
     */
    public function index(Request $request, Office $office): View
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
     * Show the form for creating a new office assignment.
     */
    public function create(Office $office): View
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
     * Store a newly created office assignment in storage.
     */
    public function store(Request $request, Office $office): RedirectResponse
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

        // If assigning as Department Head, deactivate existing department heads for this office
        if ($validated['role'] === 'Department Head') {
            OfficeAssignment::where('office_id', $office->id)
                ->where('role', 'Department Head')
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'ended_date' => now(),
                ]);
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
            'employee_id' => 'required|exists:employees,id',
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

        // Check if assignment already exists (excluding current assignment)
        $existingAssignment = OfficeAssignment::where('employee_id', $validated['employee_id'])
            ->where('office_id', $office->id)
            ->where('role', $validated['role'])
            ->where('id', '!=', $assignment->id)
            ->where('is_active', true)
            ->first();

        if ($existingAssignment) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This employee is already assigned to this office with the same role.');
        }

        try {
            DB::transaction(function () use ($validated, $office, $assignment, $employee, $user) {
                $assignment->update([
                    'employee_id' => $validated['employee_id'],
                    'user_id' => $user->id,
                    'role' => $validated['role'],
                    'assigned_date' => $validated['assigned_date'],
                    'ended_date' => $validated['ended_date'] ?? null,
                    'is_active' => $validated['is_active'] ?? true,
                    'remarks' => $validated['remarks'] ?? null,
                ]);

                // Log the assignment update
                $this->auditTrailService->logOPCRActivity(
                    'office_assignment_updated',
                    null,
                    [
                        'assignment_id' => $assignment->id,
                        'employee_id' => $assignment->employee_id,
                        'employee_name' => $employee->full_name,
                        'user_id' => $assignment->user_id,
                        'office_id' => $office->id,
                        'office_name' => $office->name,
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

        try {
            DB::transaction(function () use ($assignment, $office) {
                // Soft delete the assignment
                $assignment->delete();

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
}