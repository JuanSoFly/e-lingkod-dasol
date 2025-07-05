<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\CreateUserService;
use App\Services\AuditService;

class EmployeeController extends Controller
{
    use AuthorizesRequests;
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
        
        $query = Employee::with('user');
        
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
        
        $employees = $query->latest()->paginate(10)->appends($request->query());
        
        // Get filter options for dropdowns
        $departments = Employee::distinct()->pluck('department')->filter()->sort();
        $positions = Employee::distinct()->pluck('position')->filter()->sort();
        $employmentStatuses = Employee::distinct()->pluck('employment_status')->filter()->sort();
        
        return view('employees.index', compact('employees', 'departments', 'positions', 'employmentStatuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('employee.create');
        return view('employees.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request, CreateUserService $createUserService)
    {
        $this->authorize('employee.create');

        $createUserService->create($request->validated());

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
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
        
        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $this->authorize('employee.edit');
        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->authorize('employee.edit');

        DB::transaction(function () use ($request, $employee) {
            $validated = $request->validated();
            $employee->update($validated);

            // Also update the user's name and email if they changed
            if ($employee->user) {
                $employee->user->update([
                    'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                    'email' => $validated['email'],
                ]);
            }
        });

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $this->authorize('employee.delete');

        DB::transaction(function () use ($employee) {
            // The user will be deleted via cascade on delete if set up in the migration,
            // or we can delete it manually. It's safer to do it manually.
            if ($employee->user) {
                $employee->user->delete();
            }
            $employee->delete();
        });


        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}