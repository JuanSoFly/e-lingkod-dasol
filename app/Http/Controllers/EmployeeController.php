<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Employee;
use App\Models\User;
use App\Models\Office;
use App\Models\WorkCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\CreateUserService;
use App\Services\AuditService;
use App\Services\AuditTrailService;
use App\Services\ArchiveService;
use App\Exports\EmployeesExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ArchiveService $archiveService,
        private AuditTrailService $auditTrailService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Log access attempt
        AuditService::logEmployeeAccess(0, 'index_accessed', [
            'filters' => $request->only(['search', 'department', 'position', 'status']),
        ]);
        
        // Employees should not see all employees list
        if (auth()->user()->hasRole('Employee')) {
            AuditService::logPrivacyViolation('employee_tried_to_access_all_employees_list', [
                'route' => 'employees.index',
            ]);
            return redirect()->route('profile.edit')->with('error', 'You can only view your own profile.');
        }
        
        // Only HR Admin and Super Admin can view all employees
        $this->authorize('viewAny', Employee::class);
        
        $query = Employee::with(['user', 'office']);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('employee_number', 'LIKE', "%{$search}%")
                  ->orWhere('position', 'LIKE', "%{$search}%")
                  ->orWhere('department', 'LIKE', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }
        
        // Department filter
        if ($request->filled('department')) {
            $query->where('department', $request->get('department'));
        }
        
        // Position filter
        if ($request->filled('position')) {
            $query->where('position', $request->get('position'));
        }
        
        // Employment status filter
        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->get('employment_status'));
        }

        // Office filter
        if ($request->filled('office_id')) {
            $query->where('office_id', $request->get('office_id'));
        }

        // Department head filter
        if ($request->filled('is_department_head')) {
            $query->where('is_department_head', $request->boolean('is_department_head'));
        }
        
        $employees = $query->latest()->paginate(10)->appends($request->query());
        
        // Get filter options for dropdowns
        $departments = Employee::distinct()->pluck('department')->filter()->sort();
        $positions = Employee::distinct()->pluck('position')->filter()->sort();
        $employmentStatuses = Employee::distinct()->pluck('employment_status')->filter()->sort();
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        
        return view('employees.index', compact('employees', 'departments', 'positions', 'employmentStatuses', 'offices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('employee.create');

        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $workCalendars = WorkCalendar::orderBy('name')->get();

        return view('employees.create', compact('offices', 'workCalendars'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request, CreateUserService $createUserService): JsonResponse
    {
        $this->authorize('employee.create');

        try {
            $user = $createUserService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully. An email has been sent to ' . $user->email . ' with account setup instructions.',
                'employee_id' => $user->employee->id,
                'employee_number' => $user->employee->employee_number,
                'redirect' => route('employees.index')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Employee creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create employee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        // Log specific employee access
        AuditService::logEmployeeAccess($employee->id, 'profile_viewed', [
            'employee_number' => $employee->employee_number,
            'employee_name' => $employee->first_name . ' ' . $employee->last_name,
        ]);
        
        // Employees can only view their own profile
        if (auth()->user()->hasRole('Employee')) {
            if (auth()->user()->employee?->id !== $employee->id) {
                AuditService::logPrivacyViolation('employee_tried_to_access_other_employee_profile', [
                    'attempted_employee_id' => $employee->id,
                    'own_employee_id' => auth()->user()->employee?->id,
                ]);
                abort(403, 'You can only view your own profile.');
            }
        }
        
        $this->authorize('view', $employee);

        $employee->load(['user', 'office', 'officeAssignments.office']);

        // Get OPCR-related information for the employee
        $opcrData = [];

        // Only load OPCR data if user has permission to view OPCR
        if (auth()->user()->can('opcr.view')) {
            $opcrData = [
                'is_department_head' => $employee->isDepartmentHead(),
                'managed_offices' => $employee->managedOffices()->pluck('name'),
                'opcr_workflows' => $employee->opcrWorkflows()->with(['period', 'office'])->limit(5)->get(),
                'committed_workflows' => $employee->committedOPCRWorkflows()->with(['period', 'office'])->limit(5)->get(),
                'office_assignments' => $employee->officeAssignments()->with('office')->get(),
            ];
        }

        return view('employees.show', compact('employee', 'opcrData'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $this->authorize('employee.edit');

        $employee->load(['user', 'office', 'workCalendar']);
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $workCalendars = WorkCalendar::orderBy('name')->get();

        return view('employees.edit', compact('employee', 'offices', 'workCalendars'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->authorize('employee.edit');

        // Capture old values before update
        $oldEmployeeValues = $employee->getAttributes();
        $oldUserValues = $employee->user ? $employee->user->getAttributes() : [];

        DB::transaction(function () use ($request, $employee, &$oldEmployeeValues, &$oldUserValues) {
            $validated = $request->validated();

            // Get only the fields that are actually being updated
            $newEmployeeValues = array_intersect_key($validated, $oldEmployeeValues);

            $employee->update($validated);

            // Also update the user's name and email if they changed
            if ($employee->user) {
                $newUserValues = [
                    'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                    'email' => $validated['email'],
                ];

                $employee->user->update($newUserValues);

                // Log the comprehensive employee edit
                $this->auditTrailService->logEmployeeEdit($employee,
                    array_merge($oldEmployeeValues, $oldUserValues),
                    array_merge($newEmployeeValues, $newUserValues)
                );
            } else {
                // Log only employee changes if no user account
                $this->auditTrailService->logEmployeeEdit($employee, $oldEmployeeValues, $newEmployeeValues);
            }
        });

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Update employee office assignment
     */
    public function updateOfficeAssignment(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employee.edit');

        $request->validate([
            'office_id' => 'nullable|exists:offices,id',
            'is_department_head' => 'boolean',
        ]);

        try {
            DB::transaction(function () use ($request, $employee) {
                $oldOfficeId = $employee->office_id;
                $oldIsDepartmentHead = $employee->is_department_head;

                $employee->update([
                    'office_id' => $request->office_id,
                    'is_department_head' => $request->boolean('is_department_head', false),
                ]);

                // Update user's office if they have one
                if ($employee->user) {
                    $employee->user->update([
                        'office_id' => $request->office_id,
                    ]);
                }

                // Log the office assignment change
                $this->auditTrailService->logEmployeeEdit($employee, [
                    'office_id' => $oldOfficeId,
                    'is_department_head' => $oldIsDepartmentHead,
                ], [
                    'office_id' => $request->office_id,
                    'is_department_head' => $request->boolean('is_department_head', false),
                ]);
            });

            return back()
                ->with('success', 'Office assignment updated successfully.');

        } catch (\Exception $e) {
            \Log::error('Failed to update employee office assignment', [
                'error' => $e->getMessage(),
                'employee_id' => $employee->id,
                'request_data' => $request->all(),
            ]);

            return back()
                ->withErrors(['error' => 'Failed to update office assignment: ' . $e->getMessage()]);
        }
    }

    /**
     * Archive the specified employee.
     */
    public function destroy(Employee $employee)
    {
        $this->authorize('employee.delete');

        // Check if employee can be archived
        if (!$this->archiveService->canArchive($employee)) {
            return redirect()->route('employees.index')
                ->with('error', 'This employee cannot be archived at this time.');
        }

        try {
            $archivedEmployee = $this->archiveService->archiveEmployee($employee, auth()->user());

            return redirect()->route('employees.index')
                ->with('success', "Employee {$archivedEmployee->first_name} {$archivedEmployee->last_name} has been archived successfully. You can restore them from the archive if needed.");

        } catch (\Exception $e) {
            \Log::error('Failed to archive employee', [
                'employee_id' => $employee->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('employees.index')
                ->with('error', 'Failed to archive employee. Please try again or contact support.');
        }
    }

    /**
     * Get employees by office for AJAX requests
     */
    public function getByOffice(Request $request): JsonResponse
    {
        $request->validate([
            'office_id' => 'required|exists:offices,id',
        ]);

        $employees = Employee::where('office_id', $request->office_id)
            ->where('is_active', true)
            ->with(['user', 'office'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'employee_number' => $employee->employee_number,
                    'position' => $employee->position,
                    'email' => $employee->email,
                    'is_department_head' => $employee->is_department_head,
                    'office' => [
                        'id' => $employee->office->id,
                        'name' => $employee->office->name,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    /**
     * Get department heads by office for AJAX requests
     */
    public function getDepartmentHeads(Request $request): JsonResponse
    {
        $query = Employee::where('is_department_head', true)
            ->where('is_active', true)
            ->with(['user', 'office']);

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        $departmentHeads = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'employee_number' => $employee->employee_number,
                    'position' => $employee->position,
                    'email' => $employee->email,
                    'office' => [
                        'id' => $employee->office->id,
                        'name' => $employee->office->name,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $departmentHeads,
        ]);
    }

    /**
     * Export employees to Excel with creation timestamps
     */
    public function export(Request $request): BinaryFileResponse
    {
        // Only HR Admin and Super Admin can export employees
        $this->authorize('viewAny', Employee::class);

        $filename = 'employees_' . now()->format('Y_m_d_His') . '.xlsx';

        return Excel::download(new EmployeesExport(), $filename);
    }

    /**
     * Export filtered employees to Excel
     */
    public function exportFiltered(Request $request): BinaryFileResponse
    {
        // Only HR Admin and Super Admin can export employees
        $this->authorize('viewAny', Employee::class);

        $filename = 'employees_filtered_' . now()->format('Y_m_d_His') . '.xlsx';

        // We can enhance this later to apply the same filters as the index page
        return Excel::download(new EmployeesExport(), $filename);
    }
}
